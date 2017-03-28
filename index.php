<?php

use Symfony\Component\Yaml\Yaml;

require "vendor/autoload.php";
require_once "MemexGenerator.php";

function loadFile($path) {
	$baseUrl = 'https://raw.githubusercontent.com/kiskffmu/memex/master/';
	return file_get_contents($baseUrl . $path);
}

function loadCatData($index) {
	$path = '_topics/' . $index . '.md';
	$content = explode('---', loadFile($path));
	return Yaml::parse($content[1]); 
}

function loadOtherData($name) {
	$path = '_data/' . $name . '.yml';
	return Yaml::parse(loadFile($path)); 
}

function renderGuide() {
	$rawDataUrl = 'https://raw.githubusercontent.com/kiskffmu/memex/master/_data/memex.json';
	$json = file_get_contents(file_exists('memex.json') ? 'memex.json' : $rawDataUrl);
	$data = json_decode($json);
	
	$content = '';
	
	$content .= '<li><a href="?generator=milestones">[0.1] Milníky</a></li>';
	$content .= '<li><a href="?generator=figures">[0.2] Osobnosti</a></li>';
	$content .= '<li><a href="?generator=theories">[0.3] Teorie</a></li>';
	
	foreach ($data->categories as $cat) {
		$index = $cat->class < 10 ? '0' . $cat->class : $cat->class;
			
		$content .= '<li><a href="?generator=category&amp;index='.$index.'">['.$cat->class.'] '.$cat->name.'</a></li>';
	}
	
	return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Memex-print</title></head><body><h1>Memex print</h1><ul>'.$content.'</ul></body></html>';
}

$generator = isset($_GET['generator']) ? $_GET['generator'] : null;
switch ($generator) {
	case 'figures':
		$generator = new Memex\FiguresGenerator;
		$generator->render((object) loadOtherData('figures'));
		break;
	case 'milestones':
		$generator = new Memex\MilestonesGenerator;
		$generator->render((object) loadOtherData('milestones'));
		break;
	case 'theories':
		$generator = new Memex\TheoriesGenerator;
		$generator->render((object) loadOtherData('theories'));
		break;
	case 'category':
		$index = isset($_GET['index']) ? $_GET['index'] : null;
		$generator = new Memex\PackGenerator;
		$src = (object) loadCatData($index);
		$pack = new Memex\Pack(
			$src->class, 
			$src->name, 
			$src->guarantorName, 
			$src->guarantorUrl, 
			$src->literature, 
			$src->activities, 
			$src->sets
		);
		
		$generator->render($pack);
		break;
	default:
		echo renderGuide();
}

