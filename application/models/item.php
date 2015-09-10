<?php

/**
 * Description of item
 *
 * @author Faizan Ayubi
 */
class Item extends Shared\Model {
    /**
     * @column
     * @readwrite
     * @type text
     */
    protected $_url;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     */
    protected $_title;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     */
    protected $_image;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     */
    protected $_target;
    
    /**
     * @column
     * @readwrite
     * @type text
     */
    protected $_description;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     */
    protected $_user_id;
}