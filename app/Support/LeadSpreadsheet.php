<?php

namespace App\Support;

use App\Models\ContactMessage;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * One lead as an Excel (.xlsx) file: a header row and one data row, attached to the notification
 * email (Revisi 1: every request must arrive as a spreadsheet). The columns follow the Google Sheet
 * (docs/google-sheet-leads.md), so files can be pasted into the same sheet.
 *
 * Every text is written as a plain string cell, never parsed: a message starting with "=" stays text
 * and cannot run as a formula when the file is opened in Excel (formula injection).
 */
class LeadSpreadsheet
{
    public const HEADINGS = ['ID', 'Diterima (WIB)', 'Nama', 'Email', 'Telepon', 'Perusahaan', 'Negara', 'Layanan', 'Pesan', 'Bahasa', 'Kategori', 'Jumlah (ton)', 'Kontainer', 'Negara tujuan'];

    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public static function fileName(ContactMessage $lead): string
    {
        return 'pesan-website-'.$lead->id.'.xlsx';
    }

    /**
     * @return list<string|int|float|null>
     */
    public static function values(ContactMessage $lead): array
    {
        return [
            $lead->id,
            $lead->created_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            $lead->name,
            $lead->email,
            $lead->phone,
            $lead->company,
            $lead->country,
            $lead->service_title,
            $lead->message,
            $lead->locale,
            LeadOptions::categoryLabel($lead->category),
            $lead->quantity_tons === null ? null : (float) $lead->quantity_tons,
            LeadOptions::containerLabel($lead->container_size),
            $lead->destination_country,
        ];
    }

    /**
     * The file's bytes.
     */
    public static function xlsx(ContactMessage $lead): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lead');

        try {
            $writer = new Writer;
            $writer->getOptions()->setColumnWidth(8, 1);
            $writer->getOptions()->setColumnWidth(20, 2);
            $writer->getOptions()->setColumnWidth(24, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14);
            $writer->getOptions()->setColumnWidth(70, 9);
            $writer->openToFile($path);

            $writer->addRow(new Row(
                array_map(fn (string $heading): StringCell => new StringCell($heading, null), self::HEADINGS),
                (new Style)->setFontBold(),
            ));

            $wrap = (new Style)->setShouldWrapText();
            $writer->addRow(new Row(array_map(
                fn (string|int|float|null $value): NumericCell|StringCell => is_int($value) || is_float($value) ? new NumericCell($value, null) : new StringCell((string) $value, $wrap),
                self::values($lead),
            )));

            $writer->close();

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
        }
    }
}
