<?php
/* Copyright (C) 2026  Credit Manager module for Dolibarr */

/**
 * \file        htdocs/custom/creditmanager/reports/class/CreditExport.class.php
 * \ingroup     creditmanager
 * \brief       Export helpers for report datasets.
 */

class CreditExport
{
	/**
	 * Export rows to CSV (Excel compatible UTF-8 BOM).
	 *
	 * @param string $filename
	 * @param array<int,string> $headers
	 * @param array<int,array<int|string,mixed>> $rows
	 * @return void
	 */
	public function exportCSV($filename, $headers, $rows)
	{
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		$sep = getDolGlobalString('CREDITMANAGER_CSV_SEPARATOR', ';');
		$out = fopen('php://output', 'w');
		if ($out === false) {
			exit;
		}

		fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
		fputcsv($out, $headers, $sep);
		foreach ($rows as $row) {
			fputcsv($out, array_values($row), $sep);
		}
		fclose($out);
		exit;
	}

	/**
	 * Export payload as JSON.
	 *
	 * @param string $filename
	 * @param mixed $payload
	 * @return void
	 */
	public function exportJSON($filename, $payload)
	{
		header('Content-Type: application/json; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * Export dataset to PDF through Dolibarr TCPDF class.
	 *
	 * @param string $filename
	 * @param string $title
	 * @param array<int,string> $headers
	 * @param array<int,array<int|string,mixed>> $rows
	 * @return void
	 */
	public function exportPDF($filename, $title, $headers, $rows)
	{
		if (!class_exists('TCPDF')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
		}

		if (!class_exists('TCPDF')) {
			setEventMessages('TCPDF not available for PDF export.', null, 'errors');
			return;
		}

		$pdf = pdf_getInstance();
		$pdf->SetCreator('Dolibarr CreditManager');
		$pdf->SetAuthor('CreditManager');
		$pdf->SetTitle($title);
		$pdf->SetMargins(10, 10, 10);
		$pdf->AddPage();
		$pdf->SetFont('helvetica', '', 9);

		$html = '<h2>'.dol_escape_htmltag($title).'</h2>';
		$html .= '<table border="1" cellpadding="4">';
		$html .= '<thead><tr>';
		foreach ($headers as $header) {
			$html .= '<th><b>'.dol_escape_htmltag($header).'</b></th>';
		}
		$html .= '</tr></thead><tbody>';
		foreach ($rows as $row) {
			$html .= '<tr>';
			foreach (array_values($row) as $cell) {
				$html .= '<td>'.dol_escape_htmltag((string) $cell).'</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody></table>';

		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($filename, 'D');
		exit;
	}

	/**
	 * Export as XLSX when PhpSpreadsheet is available, else HTML table with xls mime.
	 *
	 * @param string $filename
	 * @param array<int,string> $headers
	 * @param array<int,array<int|string,mixed>> $rows
	 * @return void
	 */
	public function exportExcel($filename, $headers, $rows)
	{
		if (class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet') && class_exists('\PhpOffice\PhpSpreadsheet\Writer\Xlsx')) {
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();

			$col = 1;
			foreach ($headers as $header) {
				$sheet->setCellValueByColumnAndRow($col, 1, $header);
				$col++;
			}

			$rownum = 2;
			foreach ($rows as $row) {
				$col = 1;
				foreach (array_values($row) as $value) {
					$sheet->setCellValueByColumnAndRow($col, $rownum, (string) $value);
					$col++;
				}
				$rownum++;
			}

			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
			$writer->save('php://output');
			exit;
		}

		header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		echo "<table border=\"1\"><tr>";
		foreach ($headers as $header) {
			echo '<th>'.dol_escape_htmltag($header).'</th>';
		}
		echo '</tr>';
		foreach ($rows as $row) {
			echo '<tr>';
			foreach (array_values($row) as $cell) {
				echo '<td>'.dol_escape_htmltag((string) $cell).'</td>';
			}
			echo '</tr>';
		}
		echo '</table>';
		exit;
	}
}
