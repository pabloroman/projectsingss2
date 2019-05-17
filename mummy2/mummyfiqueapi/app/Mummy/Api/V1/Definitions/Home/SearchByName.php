<?php
namespace App\Mummy\V1\Definitions\Home;
/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Customer"))
 */
class SearchByName
{
	/**
     * @SWG\Property(example="1")
     * @var string
     */
    public $page;
    /**
     * @SWG\Property(example="10")
     * @var string
     */
    public $take;
    /**
     * @SWG\Property(example="demo")
     * @var string
     */
    public $name;

}