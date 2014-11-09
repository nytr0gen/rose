<?php
class Controller{
	public $view;
	public $model;
	
	function __construct(){
		$this->view = new View();
	}
}
?>