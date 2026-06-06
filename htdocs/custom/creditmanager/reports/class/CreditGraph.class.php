<?php
/* Copyright (C) 2026  Credit Manager module for Dolibarr */

/**
 * \file        htdocs/custom/creditmanager/reports/class/CreditGraph.class.php
 * \ingroup     creditmanager
 * \brief       Build Chart.js configs for credit reports.
 */

class CreditGraph
{
	/**
	 * Monthly consumption chart.
	 *
	 * @param array<int,array<string,mixed>> $rows
	 * @param string $chartType
	 * @return array<string,mixed>
	 */
	public function buildMonthlyConsumptionChart($rows, $chartType = 'bar')
	{
		$months = array();
		$series = array();

		foreach ($rows as $row) {
			$month = $row['month_key'];
			$key = $row['socname'].' - '.$row['credit_code'];
			$months[$month] = $month;
			if (!isset($series[$key])) {
				$series[$key] = array();
			}
			$series[$key][$month] = (float) $row['consumed_hours'];
		}

		$labels = array_values($months);
		sort($labels);

		$datasets = array();
		$index = 0;
		foreach ($series as $name => $points) {
			$color = $this->colorFromIndex($index);
			$data = array();
			foreach ($labels as $label) {
				$data[] = isset($points[$label]) ? $points[$label] : 0;
			}
			$datasets[] = array(
				'label' => $name,
				'data' => $data,
				'backgroundColor' => $this->rgba($color, 0.35),
				'borderColor' => $this->rgba($color, 1),
				'borderWidth' => 1,
				'tension' => 0.25,
			);
			$index++;
		}

		return array(
			'type' => in_array($chartType, array('bar', 'line', 'pie')) ? $chartType : 'bar',
			'data' => array('labels' => $labels, 'datasets' => $datasets),
			'options' => array(
				'responsive' => true,
				'plugins' => array(
					'legend' => array('display' => true, 'position' => 'bottom'),
					'tooltip' => array('mode' => 'index', 'intersect' => false),
				),
				'scales' => array(
					'y' => array('beginAtZero' => true, 'title' => array('display' => true, 'text' => 'Hours')),
				),
			),
		);
	}

	/**
	 * Forecast horizontal bars with status colors.
	 *
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,mixed>
	 */
	public function buildForecastChart($rows, $sortByMonths = true)
	{
		if ($sortByMonths) {
			usort($rows, function ($a, $b) {
				$ma = $a['months_remaining'];
				$mb = $b['months_remaining'];
				if ($ma === null && $mb === null) {
					return 0;
				}
				if ($ma === null) {
					return 1;
				}
				if ($mb === null) {
					return -1;
				}
				return $ma <=> $mb;
			});
		}

		$labels = array();
		$data = array();
		$colors = array();

		foreach ($rows as $row) {
			$labels[] = $row['socname'].' - '.$row['credit_code'];
			$data[] = $row['months_remaining'] === null ? 0 : round((float) $row['months_remaining'], 2);
			$status = $row['status'];
			$colors[] = $status === 'critical' ? 'rgba(220,53,69,0.8)' : ($status === 'warning' ? 'rgba(255,193,7,0.8)' : 'rgba(40,167,69,0.8)');
		}

		return array(
			'type' => 'bar',
			'data' => array(
				'labels' => $labels,
				'datasets' => array(
					array(
						'label' => 'Months remaining',
						'data' => $data,
						'backgroundColor' => $colors,
					),
				),
			),
			'options' => array(
				'indexAxis' => 'y',
				'responsive' => true,
				'plugins' => array('legend' => array('display' => false)),
				'scales' => array('x' => array('beginAtZero' => true)),
			),
		);
	}

	/**
	 * Distribution of forecast rows by status category.
	 *
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,mixed>
	 */
	public function buildForecastDistributionChart($rows)
	{
		$counts = array('safe' => 0, 'warning' => 0, 'critical' => 0);
		foreach ($rows as $row) {
			$status = $row['status'] ?? 'safe';
			if (isset($counts[$status])) {
				$counts[$status]++;
			}
		}

		return array(
			'type' => 'doughnut',
			'data' => array(
				'labels' => array('Safe (>3 months)', 'Warning (1-3 months)', 'Critical (<1 month)'),
				'datasets' => array(
					array(
						'data' => array($counts['safe'], $counts['warning'], $counts['critical']),
						'backgroundColor' => array(
							'rgba(40,167,69,0.8)',
							'rgba(255,193,7,0.8)',
							'rgba(220,53,69,0.8)',
						),
					),
				),
			),
			'options' => array(
				'responsive' => true,
				'plugins' => array('legend' => array('position' => 'bottom')),
			),
		);
	}

	/**
	 * Budget vs real grouped bars.
	 *
	 * @param array<int,array<string,mixed>> $rows
	 * @return array<string,mixed>
	 */
	public function buildBudgetVsRealChart($rows)
	{
		$labels = array();
		$budget = array();
		$real = array();

		foreach ($rows as $row) {
			$labels[] = $row['socname'].' - '.$row['credit_code'];
			$budget[] = (float) $row['budget_hours'];
			$real[] = (float) $row['real_hours'];
		}

		return array(
			'type' => 'bar',
			'data' => array(
				'labels' => $labels,
				'datasets' => array(
					array(
						'label' => 'Budget',
						'data' => $budget,
						'backgroundColor' => 'rgba(0,123,255,0.65)',
					),
					array(
						'label' => 'Real',
						'data' => $real,
						'backgroundColor' => 'rgba(255,99,132,0.65)',
					),
				),
			),
			'options' => array(
				'indexAxis' => 'y',
				'responsive' => true,
				'maintainAspectRatio' => false,
				'plugins' => array(
					'legend' => array('position' => 'bottom'),
					'tooltip' => array('mode' => 'index', 'intersect' => false),
				),
				'scales' => array(
					'x' => array('beginAtZero' => true, 'title' => array('display' => true, 'text' => 'Hours')),
					'y' => array('ticks' => array('autoSkip' => false)),
				),
			),
		);
	}

	/**
	 * @param int $index
	 * @return array{0:int,1:int,2:int}
	 */
	private function colorFromIndex($index)
	{
		$palette = array(
			array(54, 162, 235),
			array(255, 99, 132),
			array(255, 206, 86),
			array(75, 192, 192),
			array(153, 102, 255),
			array(255, 159, 64),
			array(31, 119, 180),
			array(44, 160, 44),
		);

		return $palette[$index % count($palette)];
	}

	/**
	 * @param array{0:int,1:int,2:int} $color
	 * @param float $alpha
	 * @return string
	 */
	private function rgba($color, $alpha)
	{
		return 'rgba('.$color[0].','.$color[1].','.$color[2].','.$alpha.')';
	}
}
