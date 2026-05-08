<?php

require(__DIR__ . '/../../../config.php');
require_once('../renderer.php');
require_once('classes/report.php');
require_once('lib.php');
raise_memory_limit(MEMORY_EXTRA);
require_login();

$report_id = required_param('id', PARAM_INT);
$report = null;

if ($DB->record_exists('report_wzd_report', array('id' => $report_id))) {
	$report = new block_reportwizard_report($DB->get_record('report_wzd_report', array('id' => $report_id)));
}

$context_system = context_system::instance();
$PAGE->set_context($context_system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname', 'block_reportwizard'));
$PAGE->set_url('/blocks/reportwizard/src/run_report.php');

$PAGE->navbar->ignore_active();
// $PAGE->navbar->add('Home', new moodle_url('/'));
$PAGE->navbar->add(get_string("pluginname", 'block_reportwizard'), new moodle_url('/blocks/reportwizard/src/myreports.php'));
$PAGE->navbar->add($report->name);

$PAGE->requires->js('/lib/mindatlas/jquery/jquery.min.js', true);
$PAGE->requires->js('/lib/mindatlas/jquery/ui/jquery-ui.min.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/Chartjs/Chart.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/Chartjs/Chart.bundle.js', true);
$PAGE->requires->js('/blocks/reportwizard/src/javascript/tablesorter/jquery.tablesorter.js', true);
$PAGE->requires->css('/blocks/reportwizard/src/javascript/tablesorter/themes/blue/style.css');
$PAGE->requires->css('/blocks/reportwizard/src/javascript/tablesorter/tablesorter-fix.css');
$PAGE->requires->css('/lib/mindatlas/jquery/ui/jquery-ui.min.css');
$PAGE->requires->css('/blocks/reportwizard/src/css/plugin.css');


$output = $PAGE->get_renderer('block_reportwizard');

echo $output->header();
echo $output->heading($report->name);
// @30/07/2018 enhancement
// now the $output->run_report() will return an array, the first param is the html string, the second is a boolean.
$results = $output->run_report($report);
$disabled = null;
if ($results[1] == true) {
	$disabled = 'style="display: none"';
	// $disabled = 'disabled';
}
echo '<div class="clearfix"><div class="float-right btns"><a ' . $disabled . ' class="btn btn-primary" href="' . $CFG->wwwroot . '/blocks/reportwizard/src/export_csv.php?id=' . $report->id . '">Export CSV</a></div></div>';
echo $results[0];
echo '<div class="clearfix"><div class="float-right btns"><a ' . $disabled . ' class="btn btn-primary" href="' . $CFG->wwwroot . '/blocks/reportwizard/src/export_csv.php?id=' . $report->id . '">Export CSV</a></div></div>';

?>



<script>
	$(function() {

	});


	$(document).ready(function() {

		$.tablesorter.addParser({
			id: "customDate",
			is: function(s) {
				// return s.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, [0-9]{4}|'?[0-9]{2}$/));
				return false;
			},
			format: function(s) {
				var date = s.split('/');
				return $.tablesorter.formatFloat(new Date(date[2], date[1], date[0]).getTime());
			},
			type: "numeric"
		});

		$("table.tablesorter.report-type-general").tablesorter({
			headers: {
				4: {
					sorter: "customDate"
				},
				5: {
					sorter: "customDate"
				}
			}
		});

		$("table.tablesorter.report-type-activity").tablesorter({
			headers: {
				1: {
					sorter: "customDate"
				},
			}
		});

		$("table.tablesorter.report-type-courseoverview").tablesorter({
			headers: {
				2: {
					sorter: "customDate"
				},
				3: {
					sorter: "customDate"
				}
			}
		});


	});


	<?php if ($report->type == block_reportwizard_report::REPORT_TYPE_COURSEOVERVIEW) : ?>

		var piectx = document.getElementById("overallcompletion").getContext("2d");
		var barctx = document.getElementById('coursecompletion').getContext('2d');

		var pieChartData = {
			// These labels appear in the legend and in the tooltips when hovering different arcs
			labels: [
				'<?= get_string('completed', 'block_reportwizard') ?>',
				'<?= get_string('incomplete', 'block_reportwizard') ?>',
			],

			datasets: [{
				data: pie_data,
				backgroundColor: pie_color
			}],

		};

		var barChart_labels = [];
		var barChart_data = [];
		var barChart_bgcolor = [];

		for (var i = 0; i < barchart_json.length; i++) {
			barChart_labels.push(barchart_json[i].fullname);
			barChart_data.push(barchart_json[i].average_percent);
			barChart_bgcolor.push('#cbdde6');
		}

		var barChartData = {
			labels: barChart_labels,

			datasets: [{
				label: '',
				borderWidth: 1,
				data: barChart_data,
				backgroundColor: barChart_bgcolor
			}]

		};

		window.onload = function() {

			window.myPie = new Chart(piectx, {
				type: 'pie',
				data: pieChartData,
				options: {
					legend: {
						onClick: (e) => e.stopPropagation()
					}
				}
			});

			window.myBar = new Chart(barctx, {
				type: 'bar',
				data: barChartData,
				options: {
					// responsive: true,
					scales: {
						yAxes: [{
							ticks: {
								beginAtZero: true,
								max: 100
							}
						}],
						xAxes: [{
							ticks: {
								autoSkip: false
							}
						}]
					},
					legend: {
						position: 'top',
					},
					// title: {
					// 	display: true,
					// 	text: 'Chart.js Bar Chart'
					// }
				}
			});


		};


	<?php endif; ?>
</script>

<style type="text/css">
	/*Course overview reports*/
	.report_progress_bar {
		height: 20px;
		width: 100%;
		background: #eeeeee;
		-webkit-border-radius: 4px;
		-moz-border-radius: 4px;
		border-radius: 4px;
		padding: 0px 1px;
	}

	.report_progress_text {
		height: 20px;
		color: #666;
		line-height: 20px;
		width: 100%;
		/*text-shadow: 0px 0px 3px rgba(0,0,0,0.5);*/
		text-align: center;
	}

	.report_progress_percentage {
		height: 18px;
		margin-top: -19px;
		background-color: #cbdde6;
		-webkit-border-radius: 3px;
		-moz-border-radius: 3px;
		border-radius: 3px;
	}


	/*chart js*/

	#canvas-holder {
		margin-bottom: 40px;
	}

	#diagramleft,
	#diagramright {
		width: 400px;
		float: left;
	}



	.btns {
		margin-bottom: 10px;
	}
</style>

<?php

echo $OUTPUT->footer();

?>