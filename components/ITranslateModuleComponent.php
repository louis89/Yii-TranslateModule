<?php

interface ITranslateModuleComponent
{
	
	public function getTranslateModuleID();
	
	public function setTranslateModuleID($translateModuleID = TranslateModule::ID);
	
	public function getTranslateModule();
	
}