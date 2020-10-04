<?php

class Dashboard{
	
	private $datafile;
	private $records;
	private $parameters;
	private $datasetsNames;
	private $datasets;
	private $graphs;
	private $summaryTable;
	private $lastReadingTime;
	
	public function __construct(){
		$allParameters = yaml_parse_file("./parameters.yaml");
		$this->parameters = $allParameters["parameters"];
		$this->datasetsNames = $allParameters["datasets"];
		$this->records = array();
		$this->datasets = array();
		$this->graphs = array();
	}
	
	public function addDataset($dataset){
		$this->datasets[] = $dataset;
	}
	
	public function addGraph($graph){
		$this->graphs[] = $graph;
	}
	
	public function getDataset($key){
		foreach($this->datasets as $dataset){
			if($dataset->getKey() == $key) return $dataset;
		}
		return NULL;
	}
	
	public function loadData($datafile){
		$this->datafile = $datafile;
		$allJson = file_get_contents($this->datafile);
		$allData = json_decode($allJson, true);
		$records = array();
		foreach($allData as $item){
			$record = array();
			foreach($item as $pair){
				$record[$pair["value_type"]] = floatval($pair["value"]);
			}
			$records[] = $record;
		}
		usort($records, function($a, $b) {
			return $a['time'] - $b['time'];
		});
		$this->records = $records;
	}
	
	public function createDatasets(){
		$timeMultiplier = ($this->parameters["timeFormat"] == "milliseconds") ? 1000 : 1;
		foreach($this->datasetsNames as $datasetName){
			$dataset = new Dataset();
			$dataset->setKey($datasetName["dataset"]["key"]);
			$dataset->setName($datasetName["dataset"]["name"]);
			$dataset->setOrder($datasetName["dataset"]["order"]);
			$dataset->setDisplayFormat($datasetName["dataset"]["format"]);
			$dataset->setDescription($datasetName["dataset"]["description"]);
			if(isset($datasetName["dataset"]["thresholds"])) $dataset->setThresholds($datasetName["dataset"]["thresholds"]);
			if(isset($datasetName["dataset"]["transformer"])) $dataset->setTransformerFunction($datasetName["dataset"]["transformer"]);
			$data = array();
			foreach($this->records as $record){
				$data[] = array("x" => $record["time"]*$timeMultiplier, "y" => $record[$dataset->getKey()]);
			}
			$dataset->setOriginalData($data);
			$dataset->transformData();
			$this->addDataset($dataset);
		}
	}
	
	public function getLastUpdate(){
		$lastReading = end($this->records);
		$this->lastReadingTime = DateTime::createFromFormat("U", round($lastReading["time"]));
		$now = new DateTime();
		$timeDifference = $this->lastReadingTime->diff($now);
		return $timeDifference->format("%i");
	}
	
	public function createTable(){
		$this->getLastUpdate();
		$cutoff = clone $this->lastReadingTime;
		$interval = "PT".$this->parameters["avg"]."H";
		$cutoff->sub(new DateInterval($interval));
		$tableValues = array();
		$i = 0;

		foreach($this->datasets as $dataset){
			$i = $i + 1;
			$values = NULL;
			$range = NULL;
			$order = ($dataset->getOrder() !== NULL) ? $dataset->getOrder() : $i;
			// filter the elements with x >= cutoff
			$values = array_filter($dataset->getData(), function($elt) use ($cutoff) {return (DateTime::createFromFormat("U", round($elt["x"]/1000)) > $cutoff) ;});
			
			$name = $dataset->getName();
			$lastValue = end($dataset->getData());
			$lastValue = $lastValue["y"];
			$avg = 0;
			foreach($values as $value){
				$avg += $value["y"];
			}
			$avg = round($avg / count($values)*100)/100;
			$status = "";
			$thresholds = $dataset->getThresholds();
			if($thresholds !== NULL){
				$min = $thresholds["min"];
				$max = $thresholds["max"];
				$range = "[".$min."-".$max."]";
				if($min < $avg && $avg < $max){
					$status = "";
				}
				else{
					$status = "warning";
				}
			}
			$tableValues[$order] = array("sensor" => $name, "last" => $lastValue, "avg" => $avg, "status" => $status, "range" => $range);
		}
		$this->summaryTable = $tableValues;
	}
	
	public function displayTable(){
		echo '<table class="table table-sm">
				  <thead>
					<tr>
					  <th scope="col">Sensor</th>
					  <th scope="col">Value</th>
					  <th scope="col">Avg</th>
					  <th scope="col">Range</th>
					</tr>
				  </thead>
				  <tbody>';
		foreach($this->summaryTable as $tableValue){
			echo "<tr class='table-".$tableValue["status"]."'>";
			echo "<th scope='row'>".$tableValue["sensor"]."</th>";
			echo "<td>".$tableValue["last"]."</td>";
			echo "<td>".$tableValue["avg"]."</td>";
			echo "<td>".$tableValue["range"]."</td>";
			echo "</tr>";
		}
		echo '</tbody></table>';
		
	}
	
	public function generateGraphsJavascript(){
		foreach($this->graphs as $graph){
			$graph->generateJavascript();
		}
	}
	
}

class Dataset{

	private $key;
	private $name;
	private $order;
	private $description;
	private $originalData;
	private $transformerFunction;
	private $data;
	private $displayFormat;
	private $thresholds;
	private $indicators;
	
	public function setKey($key){
		$this->key = $key;
	}
	
	public function getKey(){
		return $this->key;
	}
	
	public function setName($name){
		$this->name = $name;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function setOrder($order = NULL){
		$this->order = $order;
	}
	
	public function getOrder(){
		return $this->order;
	}
	
	public function setDescription($description = NULL){
		$this->description = $description;
	}
	
	public function setOriginalData($originalData){
		$this->originalData = $originalData;
	}
	
	public function setTransformerFunction($transformerFunction = NULL){
		$this->transformerFunction = $transformerFunction;
	}
		
	public function getData(){
		return $this->data;
	}
	
	public function setDisplayFormat($format){
		$this->displayFormat = $format;
	}
	
	public function getDisplayFormat(){
		return $this->displayFormat;
	}
	
	public function setThresholds($thresholds = NULL){
		$this->thresholds = $thresholds;
	}
	
	public function getThresholds(){
		return $this->thresholds;
	}
	
	public function setIndicators($indicators = NULL){
		$this->indicators = $indicators;
	}
	
	public function transformData(){
		$this->data = $this->originalData;
		if(isset($this->transformerFunction)){
			
		}
	}
	
}

class Graph{
	
	private $id;
	private $title;
	private $graphDataSeries;
	
	public function setId($id){
		$this->id = $id;
	}
	
	public function setTitle($title){
		$this->title = $title;
	}
	
	public function addGraphData($graphData){
		$graphData->setGraph($this);
		$this->graphDataSeries[] = $graphData;
	}
	
	public function generateJavascript(){
		$nbDatasets = count($this->graphDataSeries);
		
		$jsStringDefault = '
		/*
		
		
		*/
		var @chartId = new CanvasJS.Chart("@chartId", {
				theme: "light2", // "light1", "light2", "dark1", "dark2"
				animationEnabled: true,
				zoomEnabled: true,
				title: {
					text: "@chartTitle",
					fontSize: 14
				},
				axisX:{      
					valueFormatString: "DD-MMM HH:mm" 
				},
				@axisY
				legend:{
					cursor: "pointer",
					verticalAlign: "top",
					dockInsidePlotArea: true,
					@itemclick
				},
				data: [
				@dataSeries
				]
			});
			@chartId.render();
			@function';
			$jsDataStringDefault = '{
					type: "@type",
					name: "@serieName",
					xValueFormatString: "DD-MMM HH:mm",
					xValueType: "dateTime",
					yValueFormatString: "@yValueFormat",
					showInLegend: true,
					dataPoints: @serieDatapoints
				}';
			$jsFunctionStringDefault = 'function @functionName(e){
					if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
						e.dataSeries.visible = false;
					}
					else{
						e.dataSeries.visible = true;
					}
					@chartId.render();
				}';
			
			$itemClickString = '';
			$axisYString = '';
			$functionString = '';
			$finalDataString = '';
			$finalJsString = '';
			foreach($this->graphDataSeries as $graphData){
				$dataString = strtr($jsDataStringDefault, ["@type" => $graphData->getType(), "@serieName" => $graphData->getName(), "@yValueFormat" => $graphData->getYValuesFormat(), "@serieDatapoints" => $graphData->getData()]);
				$finalDataString .= $dataString . ", ";
			}
			$finalDataString = substr($finalDataString, 0, -2);
			if($nbDatasets > 1){
				$functionName = "toggle".ucfirst($this->id)."DataSeries";
				$functionString = strtr($jsFunctionStringDefault, ["@functionName" => $functionName, "@chartId" => $this->id]);
				$itemClickString = "itemclick: ". $functionName;
			}
			
			$finalJsString = strtr($jsStringDefault,[
														"@chartId" => $this->id,
														"@chartTitle" => $this->title,
														"@axisY" => $axisYString,
														"@itemclick" => $itemClickString, 
														"@dataSeries" => $finalDataString,
														"@function" => $functionString
													]
							);
			
			echo $finalJsString."\n";
	}
	
	public function display(){
		echo '<div id="'.$this->id.'" style="height: 370px; width: 100%;"></div>';
	}
}

class GraphData{

	private $graph;
	private $dataset;
	private $name;
	private $yValuesFormat;
	private $type;
	private $data;
	
	public function setGraph($graph){
		$this->graph = $graph;
	}
	
	public function setDataset($dataset){
		$this->dataset = $dataset;
		$this->name = $dataset->getName();
		$this->data = $dataset->getData();
		$this->yValuesFormat = $dataset->getDisplayFormat();
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getYValuesFormat(){
		return $this->yValuesFormat;
	}
	
	public function setType($type = NULL){
		$this->type = $type;
	}
	
	public function getType(){
		return ($this->type !== NULL) ? $this->type : "spline";
	}
	
	public function getData(){
		return json_encode($this->data, JSON_NUMERIC_CHECK);
	}
	
	
	
}

