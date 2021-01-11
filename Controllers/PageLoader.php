<?php
declare(strict_types=1);

namespace Scriptor\Controllers;

use Scriptor\Module;
use Scriptor\Scriptor;

/**
 * PageLoader module
 * 
 *  As the name suggests, is used to load pages
 */
class PageLoader extends Module
{
	/**
	 * A method for loading multiple pages.
	 * 
	 * By default, GET operations, which return a list of requested 
	 * pages, return only the first 25 pages. To get a different set 
	 * of items, you can use the offset and limit parameters in the 
	 * query string of the GET request.
	 * 
	 * Note, the use of the "input" here, because in this case we are 
	 * dealing with GET variables: 
	 *   ~~ ?limit=2&offset=2  ~~
	 * 
	 */
	public function getPages(): void 
	{
		$limit = ($this->input->get->limit) ? (int) $this->input->get->limit : 25;
		$offset = (int) $this->input->get->offset;
		
		$site = Scriptor::getSite();
		$active = $site->pages->getItems("active=1");
		$pages = $site->pages->sort('position', 'asc', $offset, $limit, $active);
		
		if($pages) $this->sendJsonResponse($pages, 200);
		$this->sendJsonResponse(null, 404);
	}

	/**
	 * A method for loading single pages based on a specific ID.
	 * 
	 * If the variable was passed as part of the path ~~ /pages/16 ~~ 
	 * we can access it with $args[*] ...
	 */
	public function getPageById(array $args) 
	{
		$pageId = ($args[0]) ? (int) $args[0] : 0;
		
		$site = Scriptor::getSite();
		$page = $site->pages->getItem($pageId);

		if(! $page || ! $page->active) {
			$page = null;
			$this->sendJsonResponse($page, 404);
		}

		$this->sendJsonResponse($page, 200);
	}


	/**
	 * Sends Json response and terminates the script execution.
	 *
	 * @param string|array $data - Data to send
	 */
	public function sendJsonResponse(string|array|object $data = null, 
		?int $code = 200, ?int $options = 0): void
	{
		if($code) { http_response_code($code); }

		if(! is_null($data)) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode($data, $options);
		}
		exit;
	}

}