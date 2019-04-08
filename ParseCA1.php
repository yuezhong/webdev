<?php
/* CA1 Requirements:
* - DTD
* - min 3 types of headings
* - <ul> <ol> <dl>
* - link to infotech site (new tab / window)
* - email link
* - validation
* each are worth 0.5
* coding style and indents are visually inspected worth 0.5 in total
*/


class ParseCA1
{
	private $ca1Marks;
	private $ca1Comments;
	private $dirpath;

	public function __construct($dirpath)
	{
	 // Default constructor
	 $this->ca1Marks = 0;
	 $this->ca1Comments = "";
	 $this->dirpath = $dirpath;
	}

	public function getMarks()
	{
	 return $this->ca1Marks;
	}

	public function getComments()
	{
	 return $this->ca1Comments;
	}

	// Start
	public function start($file, $student, $StudentFiles)
	{
	 //Load the HTML page
	 $html = file_get_contents($file);

	 //Create a new DOM document
	 $dom = new DOMDocument;

	 //Parse the HTML. The @ is used to suppress any parsing errors
	 @$dom->loadHTML($html);
	 
	 $username = $student->getusername();

	 $this->checkDTD($dom);
	 $this->getHeadingEmailLinkMark($dom, $username);
	 $this->countLists($dom);
	 $this->validateFile($username, $StudentFiles);
	 $this->visualChecks($student);
	} // End Start

	// See if the HTML5 doctype is used
	public function checkDTD($dom)
	{
		// <!DOCTYPE html>
		if($dom->doctype->name === 'html')
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments = "DTD present: Good";
		}
		else
		{
			$this->ca1Comments = "No HTML5 DTD found";
		}
	}

	// Calls functions to headings, email, external link
	// Adds them up to give final mark. Only get marks (0.3125)
	// if all is present, half marks for one or the other.
	public function getHeadingEmailLinkMark($dom, $studentUsername)
	{
		$heading_mark = 0;
		$maillink_mark = "";

		$heading_mark = $this->countHeadings($dom);
		$maillink_mark = $this->checkMailLink($dom);
		// echo $heading_mark . "\n" . $maillink_mark . "\n";

		if(($heading_mark == 1) && ($maillink_mark == "11"))
		{
			$this->ca1Marks += 0.75;
			$this->ca1Comments .= ";Multiple headings plus email link and external link present.";
		}
		elseif(($heading_mark == 0) && ($maillink_mark == "11"))
		{
			$this->ca1Marks += 0.5;
			$this->ca1Comments .= ";Need to have multiple headings";
		}
		elseif(($heading_mark == 1) && ($maillink_mark == "01"))
		{
			$this->ca1Marks += 0.5;
			$this->ca1Comments .= ";Missing external link.";
		}
                elseif(($heading_mark == 1) && ($maillink_mark == "10"))
                {
                        $this->ca1Marks += 0.5;
                        $this->ca1Comments .= ";Missing email link.";
                }
		elseif(($heading_mark == 1) && ($maillink_mark == "00"))
		{
			$this->ca1Comments .= ";Found multiple headings, missing email link and link to the web server.";
			$this->ca1Marks += 0.25;
		}
		elseif(($heading_mark == 0) && ($maillink_mark == "00"))
		{
			$this->ca1Comments .= ";Need to have multiple headings plus email link and link to the web server.";
		}
	} // End getHeadingEmailLinkMark

	// Count Headings, min 3 types
	public function countHeadings($dom)
	{
	$heading_type = array(
			'h1' => 0,
			'h2' => 0,
			'h3' => 0,
			'h4' => 0,
			'h5' => 0,
			'h6' => 0
	);
	$total = 0;

	// Loop through and find how many different heading types used.
	// Need a minimum of 3 different types.
	foreach($heading_type as $heading=>$value)
	{
	 $element_headings = $dom->getElementsByTagName($heading);
	 foreach($element_headings as $element)
	 {
	  if($element->nodeValue !== '')
	  {
	   $heading_type[$heading] = 1;
	  }
	 }
	}

	// Count totals.
	foreach($heading_type as $heading=>$value)
	{
		$total = $total + $value;
	}

	if($total >= 3)
	{
		return(1);
	}
	else
	{
		return(0);
	}

	} // End countHeadings


	// Check for email and external link. Needs to open in new tab or window.
	public function checkMailLink($dom)
	{
	 $mail_mark = 0;
	 $ex_mark = 0;
	 $links = $dom->getElementsByTagName('a');

	 // Email Mailto Link
	 foreach($links as $link)
	 {
	  if($link->hasAttribute('href'))
	  {
		$href = trim($link->getAttribute('href'));

		if(strtolower(substr($href, 0, 7)) === 'mailto:')
		{
		  // echo substr($href, 0, 7) . "- Email link\n";
		   $mail_mark++;
		}
	  }
	 }

	 // External Link that opens in new window or tab
	 foreach($links as $link)
	 {
	  if($link->hasAttribute('target'))
	  {
		if($link->getAttribute('target') === '_blank')
		{
		  $ex_mark++;
		}
	  }
	 }

	$retMark = "00";
	if(($ex_mark > 0) && ($mail_mark > 0))
	{
	  $retMark = "11";
	}
	elseif(($ex_mark == 0) && ($mail_mark > 0))
	{
	  $retMark = "01";
	}
	elseif(($ex_mark > 0) && ($mail_mark == 0))
	{
	  $retMark = "10";
	}

	return($retMark);
	} // End checkMailLink

	// Count lists, +1 nested
	public function countLists($dom)
	{
		$list_types = array(
						'ul' => 0,
						'ol' => 0,
						'dl' => 0
						);
		$found = 0;

		foreach($list_types as $type=>$value)
		{
		  $list = $dom->getElementsByTagName($type);
		  if($list->length > 0)
		  {
			$list_types[$type]++;
		  }
		}

		if(($list_types['ul'] > 0) && ($list_types['ol']) > 0 && ($list_types['dl'] > 0))
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";All 3 list types used: Good";
		}
		else
		{
			$this->ca1Comments .= ";Need to show all 3 list types";
		}

		// run through different combos of nested lists...
		$found += $this->countNestedList($dom, 'ul', 'ol');
		$found += $this->countNestedList($dom, 'ul', 'dl');
		$found += $this->countNestedList($dom, 'ol', 'ul');
		$found += $this->countNestedList($dom, 'ol', 'dl');
		$found += $this->countNestedList($dom, 'dl', 'ul');
		$found += $this->countNestedList($dom, 'dl', 'ol');

		if($found > 0)
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";At least 1 nested list used: Good";
		}
		else
		{
			$this->ca1Comments .= ";Need to use 1 nested list";
		}

	} // End countLists

	// Loop function to get Nested Lists
	public function countNestedList($dom, $outlist, $inlist)
	{
		$found = 0;

		foreach($dom->getElementsByTagName($outlist) as $outl)
		{
			foreach($dom->getElementsByTagName($inlist) as $inl)
			{
				$found++;
			}

		}

		return $found;
	} // End countNestedList

	/*
	// Validate HTML
	public function validateHTMLFile($dom, $file)
	{
		libxml_use_internal_errors(true);
		$dom->load($file);
		if($dom->validate())
		{
			$this->ca1Marks += 0.3125;
			$this->ca1Comments .= ";HTML validates, no errors.";
		}
		else
		{
			$this->ca1Comments .= ";HTML doesn't validate.";
		}

		// Clear errors after we're done. We don't need to store this info.
		libxml_clear_errors();
	} // End validateHTMLFile
	*/
	
	public function validateFile($username, $StudentFiles)
	{
		foreach($StudentFiles["html"] as $sfhtml)
		{
			if((strpos(strtoupper($sfhtml->getFilename()), "CA1")) && 
			($sfhtml->getusername() === $username))
			{
				$filepath = $sfhtml->getFilepath();
			}
		}
		
		if(array_key_exists($filepath, $StudentFiles["html"]))
		{
			$htmlValidate = $StudentFiles["html"][$filepath]->getValidation();
		}
		else
		{
			$htmlValidate = "n";
		}
			
		if($htmlValidate === "y")
		{
			$this->ca1Marks += 0.25;
			$this->ca1Comments .= ";HTML validates, no errors.";
		}
		else
		{
			$this->ca1Comments .= ";HTML doesn't validate.";
		}	
	}
	
	// Apply visual check scores and comments
	public function visualChecks($student)
	{
		
		if($student->getConceptMap() == 0.125)
		{
			$this->ca1Comments .= ";Must not be hand drawn. Please use software to draw.";
			$this->ca1Marks += 0.125;
		}
		elseif($student->getConceptMap() == 0)
		{
			$this->ca1Comments .= ";More work required on concept map.";
		}
		else
		{
			$this->ca1Comments .= ";Concept map is suitable: Good.";
			$this->ca1Marks += 0.25;
		}

		/* Storyboard 
		 * no storyboard = 00
		 * storyboard = 01
		 * diagrams = 10
		 */

		if($student->getStoryboards() == "01" )
		{
			$this->ca1Comments .= ";More work required on storyboard. 
			Check that your diagram matches your webpage.";
			$this->ca1Marks += 0.25;
			
		}
		elseif($student->getStoryboards() == "10")
		{
			$this->ca1Comments .= ";You should avoid using hand drawn diagrams.";
			$this->ca1Marks += 0.25;
		}
		elseif($student->getStoryboards() == "00")
		{
			$this->ca1Comments .= ";No Storyboards provided.";
		}
		else
		{
			$this->ca1Comments .= ";Storyboard is suitable: Good.";
			$this->ca1Marks += 0.5;
		}

		/* Check Coding Style
		 * coding style = 00
		 * tags = 01
		 * indent/tabs = 10
		 * all happy = 111
		 */
		if($student->getCodingStyle() == "00")
		{
			$this->ca1Comments .= ";Coding style is not consistent. Break up long lines
			so that you don't have to scroll across the screen.";
		}
		elseif($student->getCodingStyle() == "01")
		{
			$this->ca1Comments .= ";Please ensure your html, head, body tags are all on the left with no spaces in front of them. Coding style mostly ok.";
			$this->ca1Marks += 0.25;
		}
		elseif($student->getCodingStyle() == "10")
		{
			$this->ca1Comments .= ";Indenting inconsistent, subservient code block should be indented.";
			$this->ca1Marks += 0.25;
		}
		elseif($student->getCodingStyle() == "11")
		{
			$this->ca1Comments .= ";Coding Style is consistent: Good.";
			$this->ca1Marks += 0.5;
		}
	} // End visualChecks()
}

?>
