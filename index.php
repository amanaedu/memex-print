<?php

require_once "MemexGenerator.php";

$rawDataUrl = 'https://raw.githubusercontent.com/kiskffmu/memex/master/_data/memex.json';
$json = file_get_contents(file_exists('memex.json') ? 'memex.json' : $rawDataUrl);
$data = json_decode($json);

$generator = isset($_GET['generator']) ? $_GET['generator'] : null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

switch ($generator) {
	case 'figures':
		$generator = new Memex\FiguresGenerator;
		$generator->render($data->figures);
		break;
	case 'milestones':
		$generator = new Memex\MilestonesGenerator;
		$generator->render($data->milestones);
		break;
	case 'theories':
		$generator = new Memex\TheoriesGenerator;
		$generator->render($data->theories);
		break;
	case 'category':
		if (is_null($id) || !isset($data->categories[$id - 1])) {
			die('Chybne ID kategorie');
		}
	
		$generator = new Memex\PackGenerator;
		
		$src = $data->categories[$id - 1];
		
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
		echo renderGuide($data);
}

function renderGuide($data) {
	$content = '';
	
	$content .= '<li><a href="?generator=milestones">[0.1] Milníky</a></li>';
	$content .= '<li><a href="?generator=figures">[0.2] Osobnosti</a></li>';
	$content .= '<li><a href="?generator=theories">[0.3] Teorie</a></li>';
	
	foreach ($data->categories as $cat) {
		$content .= '<li><a href="?generator=category&amp;id='.$cat->class.'">['.$cat->class.'] '.$cat->name.'</a></li>';
	}
	
	return '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Memex-print</title></head><body><h1>Memex print</h1><ul>'.$content.'</ul></body></html>';
}
