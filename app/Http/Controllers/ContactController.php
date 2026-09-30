<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\Links;
use App\Support\Turnstile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * #contact form: validate, save the lead, email the team after the response.
 *
 * The lead is saved BEFORE any email is attempted and a mail failure never reaches
 * the visitor (it only flags the lead as email_failed for the admin). Works as a
 * plain HTML POST (redirect back with a flash) and as fetch/JSON.
 */
class ContactController extends Controller
{
    /** Hidden field that only bots fill in. */
    public const HONEYPOT = 'website';

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $success = SiteSetting::current()->uiTexts(app()->getLocale())['contact_success'];

        // Bot: pretend it worked, tell it nothing, store nothing.
        if (filled($request->input(self::HONEYPOT))) {
            return $this->respond($request, $success);
        }

        // Optional Cloudflare Turnstile (off by default; see App\Support\Turnstile).
        if (! Turnstile::passes($request->input(Turnstile::RESPONSE_FIELD), $request->ip())) {
            $failed = __('Security check failed. Reload the page and try again.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $failed], 422);
            }

            return redirect(Links::section('contact'))
                ->with('contact_error', $failed)
                ->withInput($request->except(self::HONEYPOT));
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-().\s]{5,40}$/'],
            'company' => ['nullable', 'string', 'max:150'],
            'service_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'name.required' => __('Please enter your name.'),
            'email.required' => __('Please enter your email address.'),
            'email.email' => __('Please enter a valid email address.'),
            'phone.regex' => __('Please enter a valid phone number.'),
            'message.required' => __('Please write your message.'),
            'message.min' => __('Your message is too short.'),
            '*.max' => __('This field is too long.'),
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($request->expectsJson()) {
                return response()->json(['message' => __('Please check the form.'), 'errors' => $errors], 422);
            }

            return redirect(Links::section('contact'))
                ->withErrors($errors, 'contact')
                ->withInput($request->except(self::HONEYPOT));
        }

        $data = $validator->validated();
        $service = filled($data['service_id'] ?? null) ? Service::query()->active()->whereKey((int) $data['service_id'])->first() : null;

        $message = ContactMessage::query()->create([
            'name' => $this->clean($data['name']),
            'email' => $data['email'],
            'phone' => $this->clean($data['phone'] ?? null),
            'company' => $this->clean($data['company'] ?? null),
            'service_id' => $service?->id,
            'service_title' => $service ? ($service->translate('title', 'id') ?: $service->translate('title')) : null,
            'message' => trim($data['message']),
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        dispatch(fn () => self::notify($message))->afterResponse();

        return $this->respond($request, $success);
    }

    /**
     * Email every recipient (one message each, so one bad address cannot block the
     * others). Runs after the response; any failure only flags the lead.
     */
    public static function notify(ContactMessage $message): void
    {
        $recipients = SiteSetting::current()->contactRecipients();
        $failed = $recipients === [];

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new ContactMessageReceived($message));
            } catch (Throwable $e) {
                $failed = true;
                Log::warning('Contact email failed', ['message_id' => $message->id, 'recipient' => $recipient, 'error' => $e->getMessage()]);
            }
        }

        if ($failed) {
            $message->forceFill(['email_failed' => true])->save();
        }
    }

    private function respond(Request $request, string $text): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $text]);
        }

        return redirect(Links::section('contact'))->with('contact_status', $text);
    }

    /**
     * Single-line text without control characters (names go into email headers).
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }
}
