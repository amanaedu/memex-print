<?php

namespace Memex;

use FPDI;

class Generator 
{	
	protected $pdf;
	protected $questionPadding = 1; //2.9;
	protected $errors = 0;
	protected $log = array();
	protected $handOverUrl;
	
	public function __construct() 
	{	
		require_once('./TCPDF-master/tcpdf.php');
		require_once('./FPDI-1.6.1/fpdi.php');
		
		$this->pdf = new FPDI();
		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);

		// set document information
		$this->pdf->SetCreator(PDF_CREATOR);
		$this->pdf->SetAuthor('KISK');
		$this->pdf->SetTitle('Memex');
	}
	
	public function setupHandOver($url)
	{
		$this->handOverUrl = $url; 
	}
	
	protected function processHandOver($args)
	{
		$data = array(
			'args' => serialize($args),
			'class' => get_class($this),
			'log' => $this->log
		);

		$options = array(
		    'http' => array(
		        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
		        'method'  => 'POST',
		        'content' => http_build_query($data)
		    )
		);
		
		$context  = stream_context_create($options);
		$result = file_get_contents($this->handOverUrl, false, $context);
		if ($result === FALSE) { 
			echo "MemexGenerator HandOver malfunction. Please, try again in a minute.";
			exit;
		}

		$filename = 'memex.pdf';
		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . strlen($result));
		header('Accept-Ranges: bytes');
		echo $result;
		exit;
	}
	
	
	public function init($template) 
	{	
		if (substr($template, 0, 4) == 'http') {
			// cache loaded template
			// provide filename	
		}
		$this->pdf->setSourceFile($template);
		$this->pdf->SetAutoPageBreak(false);
	}
	
	public function close($filename)
	{	
		$this->flushLog();
		$this->pdf->Output($filename . '.pdf', 'I');
		exit;
	}
	
	public function log($message, $error = false)
	{
		$this->log[] = $message;
		if ($error) {
			$this->errors++;
		}
	}

	
	#######
	#       #      ###### #    # ###### #    # #####  ####
	#       #      #      ##  ## #      ##   #   #   #
	#####   #      #####  # ## # #####  # #  #   #    ####
	#       #      #      #    # #      #  # #   #        #
	#       #      #      #    # #      #   ##   #   #    #
	####### ###### ###### #    # ###### #    #   #    ####
	
	
	protected function title($class, $text) {
		$this->pdf->setCellHeightRatio(2);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(7.5, 7.5, 15, 15, $class, 0, 0, false, true, 'C');
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetFont('proximanovarg', 'b', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(100, 50, 24.1, 17, $text, 0, 0, false, true, 'L');
	}
	
	protected function invertedTitle($class, $text) {
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetTextColor(0,0,0,0);
		
		$this->pdf->SetFont('proximanovaxb', '', 8);
		$this->pdf->writeHTMLCell($class ? 85 : 91, 5, 22.5, 16.5, $text, 0, 0, false, true, 'R');
		
		if ($class) {
			$this->pdf->setCellHeightRatio(1.8);
			$this->pdf->SetFont('proximanovaxb', '', 7);
			$this->pdf->writeHTMLCell(10, 5, 101.5, 15.7, '['.$class.']', 0, 0, true, true, 'R');	
		}
	}
	
	protected function question($y, $num, $text, $class = null, $inverted = false) {
		$this->pdf->setCellHeightRatio(2);
		if ($inverted) {
			$this->pdf->SetTextColor(0,0,0,0);	
		} else {
			$this->pdf->SetTextColor(0,0,0,100);	
		}
		
		// number
		$this->pdf->SetFont('proximanovaxb', '', 7);
		if ($inverted) {
			$this->pdf->SetTextColor(0,0,100,0);
		} else {
			$this->pdf->Rect(15.9, $y, 5, 5, 'F', array(), array(0,0,100,0));
		}
		$this->pdf->setCellPadding(0);
		$this->pdf->writeHTMLCell(5, 5, 15.9, $y, $num, 0, 0, false, true, 'C');
		$this->pdf->SetFillColor(0,0,0,0);

		// class
		if (!is_null($class)) {
			if ($inverted) {
				$this->pdf->SetTextColor(0,0,0,0);
			} else {
				$this->pdf->setCellHeightRatio(1.8);
				$this->pdf->SetFont('proximanovaxb', '', 7);
				$this->pdf->writeHTMLCell(10, 5, 101.5, $y+.2, '['.$class.']', 0, 0, true, true, 'R');	
			}
		}
		
		
		// question
		if ($inverted) {
			$this->pdf->SetTextColor(0,0,0,0);
		}
		 
		$this->pdf->setCellHeightRatio(1.2);
		$this->pdf->SetFont('proximanovarg', '', 8);
		$this->pdf->writeHTMLCell($inverted || is_null($class) ? 90 : 80, 5, 22.5, $y+.8, $this->improveTypography($text), 0, 1, true, false, 'L');
		
		return $this->pdf->getY();
	}
	
	protected function largeTitle($name) 
	{
		$this->pdf->SetFont('proximanovaxb', '', 18);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->SetFillColor(0,0,0,0);
		$this->pdf->writeHTMLCell(90, 50, 15.9, 51, '<b></b>' . $name, 0, 1, true, false, 'L');
	}
	
	protected function quote($quote, $quoteSource) 
	{
		$this->pdf->SetFont('proximanovabl', '', 16);
		$this->pdf->SetTextColor(0,0,100,0);
		$this->pdf->writeHTMLCell(83, 10, 23.5, 19, '<b></b>„', 0, 1, true, false);
		
		$this->pdf->SetFont('proximanovasb', '', 8);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->SetFillColor(0,0,100,0);
		$this->pdf->writeHTMLCell(83, 10, 29, 22.2, '<b></b><i>' . strip_tags($quote, '<i><em><b><strong>') . '</i>', 0, 1, true, false);
		if (trim($quoteSource)) {
			$this->pdf->writeHTMLCell(83, 10, 29, $this->pdf->getY(), '<b></b>&mdash;'.$quoteSource.'</p>', 0, 1, true, false, "R");	
		} else {
			$this->pdf->setY($this->pdf->getY()+5);
		}
		
	}
	
	
	
	
	
	 #####
	#     # ##### #####  #    #  ####  ##### #    # #####  ######
	#         #   #    # #    # #    #   #   #    # #    # #
	 #####    #   #    # #    # #        #   #    # #    # #####
	      #   #   #####  #    # #        #   #    # #####  #
	#     #   #   #   #  #    # #    #   #   #    # #   #  #
	 #####    #   #    #  ####   ####    #    ####  #    # ######
	
	protected function addCard(
		$width = 128, $height = 103, 
		$tplPage = 1, $tplX = 0, $tplY = 0
	) {
		$tpl = $this->pdf->importPage($tplPage, "TrimBox");
		$this->pdf->AddPage("L", array($width, $height));
		$this->pdf->useTemplate($tpl, $tplX, $tplY);
		$this->drawCropmarks($width, $height, 11.5);
	}
	
	protected function drawCropmarks($width, $height, $size)
	{
		$this->pdf->cropMark($size,$size,$size,$size,'TL');
		$this->pdf->cropMark($width-$size,$size,$size,$size,'TR');
		$this->pdf->cropMark($size,$height-$size,$size,$size,'BL');
		$this->pdf->cropMark($width-$size,$height-$size,$size,$size,'BR');
	}
	
	protected function prepQuestionCard($packClass, $tplX = 8.5, $tplY = 8)
	{
		$this->addCard(128, 103, $packClass, $tplX, $tplY);
		//bg
		$this->pdf->Rect(7, 7, 114, 79.5, 'F', array(), array(0,0,0,0)); 
		//flag
		$this->pdf->Rect(15, 15, 7.5, 7.5, 'F', array(), array(0,0,0,100));
		//line
		$this->pdf->Line(15, 22.5, 113.5, 22.5, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,100)));
	}
	
	protected function prepAnswerCard($packClass)
	{
		$this->addCard(128, 103, $packClass, 8.5, 8);
		//bg
		$this->pdf->Rect(7, 7, 114, 79.5, 'F', array(), array(0,0,0,100));
		//line
		$this->pdf->Line(15, 22.5, 113.5, 22.5, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,0)));
	}

	

	
	
	#     #
	#     # ###### #      #####  ###### #####   ####
	#     # #      #      #    # #      #    # #
	####### #####  #      #    # #####  #    #  ####
	#     # #      #      #####  #      #####       #
	#     # #      #      #      #      #   #  #    #
	#     # ###### ###### #      ###### #    #  ####
	
	protected function writeBullet($text, $y, $bullet = '⊲')
	{
		$this->pdf->SetTextColor(0,0,100,0);
		$this->pdf->writeHTMLCell(10, 0, 15, $y, '<b></b>' . $bullet, 0, 1, false, true);
		
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(90, 0, 20, $y, '<b></b>' . $text, 0, 1, false, false);
		
		return $this->pdf->getY();
	}
	
	protected function removeTags($text)
	{
		return trim(strip_tags($text));
	}
	
	protected function improveTypography($text) 
	{
		// lamani slov
		$words = explode(' ', $text);
		foreach ($words as $i => $word) {
			if (strlen($word) > 6 && strpos($word, '>') === FALSE && strpos($word, '<') === FALSE) {
				$created = mb_substr($word, 0, 3, 'UTF-8');
				// POSIX ereg_replace is used because of UTF8 support #sad
				$created .= mb_ereg_replace("([AÁEÉĚIÍOÓUÚŮYÝaáeéěiíoóuúůyý])([BCČDĎFGHJKLMNŇPQRŘSŠTŤVWXZŽbcčdďfghjklmnňpqrřsštťvwxzž][^BCČDĎFGHJKLMNŇPQRŘSŠTŤVWXZŽbcčdďfghjklmnňpqrřsštťvwxzž])", "\\1&shy;\\2", mb_substr($word, 3, -2, 'UTF-8'));	
				$created .= mb_substr($word, -2, NULL, 'UTF-8');
				$words[$i] = $created;
			}
		}
		$text = implode(' ', $words);
		
		// predlozky
		$text = preg_replace("/ ([svzkuoiaSVZKAUOI]) /", " $1&nbsp;", $text); 
		$text = preg_replace("/ (č\.) /", " $1&nbsp;", $text); 
		
		// uvozovky
		//$text = preg_replace('# ["“”„]#', " &laquo;", $text);
		//$text = preg_replace('#["“”„] #', "&raquo; ", $text);
		
		// pomlcky
		$text = str_replace(' - ', ' &ndash; ', $text);
		
		return $text;
	}
	
	protected function flushLog()
	{
		$this->pdf->SetMargins(5, 5, 5, 5);
		$this->pdf->AddPage("L", array(128.28, 103.28));
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->setCellPadding(0);
		$this->pdf->SetFont('proximanovarg', '', 6);
		$this->pdf->SetAutoPageBreak(true);	
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->write(1, "LOG:\n");	
		if (!count($this->log)) {
			$this->pdf->write(1, 'Nothing to report.' . "\n");
		}
		foreach ($this->log as $entry) {
			$this->pdf->write(1, $entry . "\n");	
		}
	}
}

class MilestonesGenerator extends Generator
{
	public function render($milestones) 
	{
		if ($this->handOverUrl) {
			$this->processHandOver(func_get_args());
		}
		$this->init('./tpl/MemexBlankTemplate.pdf');	
		foreach ($milestones as $milestone) {
			$this->renderCard((object) $milestone);
		}
		$this->close('milestones.pdf');
	}
	
	public function renderCard($milestone) 
	{
		// front
		$this->addCard(103, 58);
		//bg
		
		//flag
		$this->pdf->Rect(15, 15, 4.7, 4.7, 'F', array(), array(0,0,0,100));
		//line
		$this->pdf->Line(15, 19.7, 88.5, 19.7, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,100)));
		
		
		$this->pdf->setCellHeightRatio(1.8);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->SetFont('proximanovabl', '', 8);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(4.7, 4.7, 15, 15, "!", 0, 0, false, true, 'C');
		
		$this->pdf->SetFont('proximanovarg', 'b', 8);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(100, 50, 21, 15, "Milníky", 0, 0, false, true, 'L');
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->writeHTMLCell(100, 50, 17, 26, $milestone->time, 0, 0, false, true, 'L');
		
		$this->pdf->SetFont('proximanovarg', '', 8);
		$this->pdf->writeHTMLCell(100, 50, 17, 31, $milestone->short, 0, 0, false, true, 'L');
		
		
		$this->addCard(103, 58);
		$this->pdf->Rect(8.5, 8.5, 86, 41, 'F', array(), array(0,0,0,100)); 
		$this->pdf->Line(15, 19.7, 88.5, 19.7, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,0)));
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->SetFont('proximanovarg', 'b', 8);
		$this->pdf->writeHTMLCell(73.5, 5, 15, 13.8, "Milníky", 0, 0, false, true, 'R');
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->writeHTMLCell(100, 50, 17, 24, $milestone->short, 0, 0, false, true, 'L');
		
		$this->pdf->SetFont('proximanovarg', '', 8);
		$height = $this->pdf->getCellHeightRatio();
		$this->pdf->setCellHeightRatio($height * .8);
		$this->pdf->writeHTMLCell(70, 50, 17, 31, $milestone->long, 0, 0, false, true, 'L');
		$this->pdf->setCellHeightRatio($height);
	}
}

class TheoriesGenerator extends Generator
{
	public function render($theories) 
	{
		if ($this->handOverUrl) {
			$this->processHandOver(func_get_args());
		}
		$this->init('./tpl/bgs.pdf');
		
		$this->renderDividerCard();
		
		foreach ($theories as $category) {
			foreach ($category as $theory) {
				$theory = (object) $theory;
				
				// front
				$this->prepQuestionCard(20);
				$this->title("▼", "Teorie");
				$this->largeTitle($theory->name);
				
				// back
				$this->prepTheoryCard();
				$this->invertedTitle('', $theory->category);
				
				$this->pdf->SetFont('proximanovasb', '', 8);
				$this->pdf->setCellHeightRatio(1.3);
				$y = $this->writeBullet($this->removeTags($theory->description), 27);
				$this->writeBullet($this->removeTags($theory->examples), $y+$this->questionPadding*2, '&#9679;');
			}
		}
		$this->close('theories');
		
	}
	
	private function renderDividerCard() {
		$this->addCard(128, 103, 20, 8.5, 8);	
		$this->catTitle("▼", "Teorie");
	}
	
	protected function catTitle($class, $text) {
		$this->pdf->setCellHeightRatio(2);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->Rect(15, 15, 7.5, 7.5, 'F', array(), array(0,0,0,0));
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(7.5, 7.5, 15, 15, $class, 0, 0, false, true, 'C');
		
		$this->pdf->startTransaction(); 
		$start_x = $this->pdf->GetX(); 
		$this->pdf->write(50, $text);
		$width = ($this->pdf->GetX() - $start_x);
		$this->pdf = $this->pdf->rollbackTransaction();
		
		$this->pdf->Rect(23+3, 15, $width+4, 7.5, 'F', array(), array(0,0,0,0));
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetFont('proximanovarg', 'b', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(100, 50, 24.1+4, 17, $text, 0, 0, false, true, 'L');
	}
	
	
	private function prepTheoryCard()
	{
		$this->addCard(128, 103, 20, 8.5, 8);
		//bg
		$this->pdf->Rect(7, 7, 114, 87, 'F', array(), array(0,0,0,100));
		//line
		$this->pdf->Line(15, 22.5, 113.5, 22.5, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,0)));
	}	
}

class FiguresGenerator extends Generator
{
	public function render($figures) 
	{
		if ($this->handOverUrl) {
			$this->processHandOver(func_get_args());
		}
		$this->init('./tpl/bgs.pdf');
		
		$this->renderDividerCard();
		
		foreach ($figures as $figure) {
			$this->renderCard((object) $figure);
		}
		$this->close('figures');
	}
	
	private function renderDividerCard() {
		$this->addCard(128, 103, 19, 8.5, 8);	
		$this->catTitle("●", "Osobnosti");
	}
	
	protected function catTitle($class, $text) {
		$this->pdf->setCellHeightRatio(2);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->Rect(15, 15, 7.5, 7.5, 'F', array(), array(0,0,0,0));
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(7.5, 7.5, 15, 15, $class, 0, 0, false, true, 'C');
		
		$this->pdf->startTransaction(); 
		$start_x = $this->pdf->GetX(); 
		$this->pdf->write(50, $text);
		$width = ($this->pdf->GetX() - $start_x);
		$this->pdf = $this->pdf->rollbackTransaction();
		
		$this->pdf->Rect(23+3, 15, $width+4, 7.5, 'F', array(), array(0,0,0,0));
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetFont('proximanovarg', 'b', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(100, 50, 24.1+4, 17, $text, 0, 0, false, true, 'L');
	}
	
	public function renderCard($figure) 
	{
		// front
		$this->prepQuestionCard(19);
		
		$this->title("●", "Osobnosti");
		$this->largeTitle($figure->name);
		
		// back
		$this->prepFigureInfoCard($figure->born, $figure->deceased);
		$this->pdf->setCellHeightRatio(1.3);
		$this->quote($figure->quote, $figure->quoteSource);
		
		$y = $this->pdf->getY();
		foreach ($figure->bio as $item) {
			$y = $this->writeBullet($item, $y);
		}
	}
	
	protected function prepFigureInfoCard($born, $deceased)
	{
		$this->addCard(128, 103, 19, 8.5, 8);
		$this->pdf->SetMargins(29, 30, 15, 30);
		//bg
		$this->pdf->Rect(7, 7, 114, 87, 'F', array(), array(0,0,0,100));
		
		//line
		$lcuttoff = $born ? 9 : 0;
		$rcuttoff = $deceased ? 10 : 0;
		$this->pdf->Line(15+$lcuttoff, 16.5, 113.5-$rcuttoff, 16.5, 
			array('width' => .4, 'cap' => 'butt', 'color' => array(0,0,0,0)));
		
		// bord/deceased
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->SetFont('proximanovabl', '', 8);
		$this->pdf->SetCellPadding(0);
		
		$this->pdf->Text(15, 15, '*'.$born, false, false, true, 0, 0, 'L');
		$this->pdf->Text(15, 15, '†'.$deceased, false, false, true, 0, 0, 'R');
	}
}


class PackGenerator extends Generator
{
	public function render($pack)
	{
		$pack = (object) $pack;
		
		if ($this->handOverUrl) {
			$this->processHandOver(func_get_args());
		}
		$this->init('./tpl/bgs.pdf');
		
		$shuffled = array_chunk($pack->getShuffledQuestions(), 5);
		
		
		$this->generateDividerCard($pack);
		$this->generateActivitiesCard($pack);
		$this->generateLiteratureCard($pack);
		$this->generateOpenQuestionsCard($pack);
		
		foreach ($shuffled as $chunk) {
			$this->generateClosedQuestionsCard($pack, $chunk);
		}
		$this->close('memex');
		
	}
	
	private function generateDividerCard($pack)
	{
		$this->addCard(128, 103, $pack->class, 8.5, 8);	
		$this->catTitle($pack->class, $pack->name);
	}
	
	protected function catTitle($class, $text) {
		$this->pdf->setCellHeightRatio(2);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->Rect(15, 15, 7.5, 7.5, 'F', array(), array(0,0,0,100));
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(7.5, 7.5, 15, 15, $class, 0, 0, false, true, 'C');
		
		$this->pdf->startTransaction(); 
		$start_x = $this->pdf->GetX(); 
		$this->pdf->write(50, $text);
		$width = ($this->pdf->GetX() - $start_x);
		$this->pdf = $this->pdf->rollbackTransaction();
		
		$this->pdf->Rect(23+3, 15, $width+2, 7.5, 'F', array(), array(0,0,0,100));
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetFont('proximanovarg', 'b', 11);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(100, 50, 24.1+4, 17, $text, 0, 0, false, true, 'L');
	}
	
	private function generateActivitiesCard($pack)
	{
		$this->prepQuestionCard($pack->class);
		
		$this->title('#', 'Vzdělávací výzvy');
		
		$y = 26.8;
		
		foreach ($pack->activities as $i => $activity) {
			$y = $this->question($y, $i+1, '<b></b>' . $activity);
			$y += 2.9;
		}
	}
	
	private function generateLiteratureCard($pack) 
	{
		$this->prepAnswerCard($pack->class);
		
		$this->litTitle('#', 'Doporučené rozšiřující knihy');
		
		$y = 26.8;
		
		foreach ($pack->literature as $i => $lit) {
			$y = $this->question($y, $i+1, '<b></b>' . $lit, null, true);
			$y += 2.9;
		}
	}
	
	protected function litTitle($class, $text) {
		$this->pdf->setCellHeightRatio(2);
		$this->pdf->setCellPadding(0);
		
		$this->pdf->Rect(15, 15, 7.5, 7.5, 'F', array(), array(0,0,0,0));
		
		$this->pdf->SetFont('proximanovabl', '', 11);
		$this->pdf->SetTextColor(0,0,0,100);
		$this->pdf->writeHTMLCell(7.5, 7.5, 15, 15, $class, 0, 0, false, true, 'C');
		
		$this->pdf->setCellHeightRatio(1);
		$this->pdf->SetFont('proximanovarg', 'b', 11);
		$this->pdf->SetTextColor(0,0,0,0);
		$this->pdf->writeHTMLCell(100, 50, 24.1, 17, $text, 0, 0, false, true, 'L');
	}
	
	public function generateClosedQuestionsCard($pack, $questions)
	{
		// page 1
		$this->prepQuestionCard($pack->class);
		$this->title($pack->class, $pack->name);
		$y = 26.8;
		foreach ($questions as $i => $question) {
			$y = $this->question($y, $i+1, '<b></b>' . $question->question, $question->class);
			$y += 2.9;
		}
		
		// page 2
		$this->prepAnswerCard($pack->class);
		$this->invertedTitle($pack->class, $pack->name);
		$y = 26.8;
		foreach ($questions as $i => $question) {
			$y = $this->question($y, $i+1, '<b></b>' . $question->answer, $question->class, true);
			$y += 2.9;
		}
	}
	
	public function generateOpenQuestionsCard($pack)
	{
		$questions = array();
		$totalLength = 0;
		$lengthWritten = 0;
		foreach ($pack->sets as $set) {
			$questions[] = $set->open;
			$totalLength += mb_strlen($set->open, 'UTF-8');
		}
		$questionCount = count($questions);
		
		$this->prepQuestionCard($pack->class);
		$this->title($pack->class, 'Otevřené otázky /a');
		$y = 26.8;
		foreach ($questions as $i => $question) {
			$y = $this->question($y, $i+1, '<b></b>' . $question, null);
			$y += 2.9;
			unset($questions[$i]);
			
			if ($lengthWritten += mb_strlen($question, 'UTF-8') > $totalLength/2 || $i+2 > $questionCount/2) {
				break;
			}
		}	
		
		$this->prepQuestionCard($pack->class);
		$this->title($pack->class, 'Otevřené otázky /b');
		$y = 26.8;
		foreach ($questions as $i => $question) {
			$y = $this->question($y, $i+1, '<b></b>' . $question, null);
			$y += $this->questionPadding;
		}	
	}
}


class Figure 
{
	public $name;
	public $born;
	public $deceased;
	public $quote;
	public $quoteSource;
	public $bio;
	
	public function __construct($name, $born, $deceased, $quote, $quoteSource, $bio) {
		$this->name = $name;		
		$this->born = $born;
		$this->deceased = $deceased;
		$this->quote = $quote;
		$this->quoteSource = $quoteSource;
		$this->bio = array();
		
		// bio cleanup
		$items = explode("\n", $bio);
		foreach ($items as $key => $item) {
			$item = trim(strip_tags($item, '<i><em><b><strong>'));
			if ($item) {
				$this->bio[$key] = $item;
			}
		}
	}
}

class Milestone 
{
	public $time;
	public $short;
	public $long;
	
	public function __construct($time, $short, $long)
	{
		$this->time = $time;
		$this->short = $short;
		$this->long = $long;	
	}
}

class Theory
{
	public $name;
	public $category;
	public $description;
	public $examples;
	
	public function __construct($name, $category, $description, $examples)
	{
		$this->name = $name;
		$this->category = $category;
		$this->description = $description;
		$this->examples = $examples;
	}
}

class Pack 
{
	public $class;
	public $name;
	public $guarantorName;
	public $guarantorUrl;
	public $activities = array();
	public $literature = array();
	public $sets = array();
	
	public function __construct($class, $name, $guarantorName, $guarantorUrl, $literature, $activities, $sets) 
	{
		$this->class = $class;
		$this->name = $name;
		$this->guarantorName = $guarantorName;
		$this->guarantorUrl = $guarantorUrl;
		$this->activities = $activities;
		$this->literature = $literature;
		
		foreach ($sets as $src) {
			$src = (object) $src;
			
			$setObj = new Set($src->class, $src->name, $src->open);
			
			foreach ($src->closedQuestions as $srcQuestion) {
				$srcQuestion = (object) $srcQuestion;
				
				$qObj = new ClosedQuestion($srcQuestion->question, $srcQuestion->answer);
				$setObj->addClosedQuestion($qObj);
			}
			
			$this->addSet($setObj);
		}
	}
	
	public function getShuffledQuestions()
	{
		$shuffled = array();
		foreach ($this->sets as $set) {
			foreach ($set->closedQuestions as $question) {
				$shuffled[] = $question;
			}
		}
		
		shuffle($shuffled);
		$this->log[] = "Total question count: " . count($shuffled);
		return $shuffled;
	}
	
	public function addSet(Set $set)
	{
		$classNumber = (int) substr($set->class, strpos($set->class, '.')+1);
		$this->sets[$classNumber] = $set;
		ksort($this->sets);
	}
}

class Set
{
	public $class;
	public $name;
	public $open;
	public $closedQuestions = array();
	
	public function __construct($class, $name, $open) 
	{
		$this->class = $class;
		$this->name = $name;
		$this->open = $open;
	}
	
	public function addClosedQuestion(ClosedQuestion $question) 
	{
		$question->class = $this->class;
		$this->closedQuestions[] = $question;
	}
}

class ClosedQuestion
{
	public $question;
	public $answer;
	public $class;
	
	public function __construct($question, $answer) {
		$this->question = $question;
		$this->answer = $answer;
	}
}

