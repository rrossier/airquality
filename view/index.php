<?php
include("classes.php");

$dashboard = new Dashboard();
$dashboard->loadData("../api/data.json");
$dashboard->createDatasets();
$dashboard->createTable();

//
$graph1 = new Graph();
$graph1->setId("pm1Chart");
$graph1->setTitle("Particulates");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("P0.3_air"));
$graphData1->setType("area");
$graph1->addGraphData($graphData1);
$graphData2 = new GraphData();
$graphData2->setDataset($dashboard->getDataset("P0.5_air"));
$graphData2->setType("area");
$graph1->addGraphData($graphData2);
//
$graph2 = new Graph();
$graph2->setId("pm2Chart");
$graph2->setTitle("Particulates");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("P1_air"));
$graphData1->setType("area");
$graph2->addGraphData($graphData1);
$graphData2 = new GraphData();
$graphData2->setDataset($dashboard->getDataset("P2.5_m3"));
$graphData2->setType("area");
$graph2->addGraphData($graphData2);
$graphData3 = new GraphData();
$graphData3->setDataset($dashboard->getDataset("P10_m3"));
$graphData3->setType("area");
$graph2->addGraphData($graphData3);
//
$graph3 = new Graph();
$graph3->setId("tempChart");
$graph3->setTitle("Temperature & Humidity");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("temperature"));
$graph3->addGraphData($graphData1);
$graphData2 = new GraphData();
$graphData2->setDataset($dashboard->getDataset("humidity"));
$graph3->addGraphData($graphData2);
//
$graph4 = new Graph();
$graph4->setId("oxiChart");
$graph4->setTitle("Nitrogen dioxide (positively correlated)");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("oxidising"));
$graph4->addGraphData($graphData1);
//
$graph5 = new Graph();
$graph5->setId("reduChart");
$graph5->setTitle("Carbon monoxide (negatively correlated)");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("reducing"));
$graph5->addGraphData($graphData1);
//
$graph6 = new Graph();
$graph6->setId("nh3Chart");
$graph6->setTitle("Ammonia (negatively correlated)");
$graphData1 = new GraphData();
$graphData1->setDataset($dashboard->getDataset("nh3"));
$graph6->addGraphData($graphData1);

//
$dashboard->addGraph($graph1);
$dashboard->addGraph($graph2);
$dashboard->addGraph($graph3);
$dashboard->addGraph($graph4);
$dashboard->addGraph($graph5);
$dashboard->addGraph($graph6);
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="Romain Rossier">
    <title>AirQuality Dashboard</title>
	<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
    
    <script type="text/javascript">
		window.onload = function () {
			 <?php $dashboard->generateGraphsJavascript(); ?>
			}
	</script>
</head>
<body>


	<div class="container-fluid">
		<div class="row">
			<div class="">
				<h1 class="h2">Dashboard <small class="text-muted">updated <?php echo $dashboard->getLastUpdate(); ?> mins ago</small></h1>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<?php $graph1->display(); ?>
			</div>
			<div class="col">
				<?php $graph2->display(); ?>
			</div>
			<div class="col">
				<?php $graph3->display(); ?>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<?php $graph4->display(); ?>
			</div>
			<div class="col">
				<?php $graph5->display(); ?>
			</div>
			<div class="col">
				<?php $graph6->display(); ?>
			</div>
		</div>
		<div class="row">
			<div class="col">
				<?php $dashboard->displayTable(); ?>
			</div>
			<div class="col">
				
			</div>
		</div>
	</div>
	<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
	
	
</body>
</html>
