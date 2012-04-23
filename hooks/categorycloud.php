<?php defined('SYSPATH') or die('No direct script access'); 
/**
 * Category Cloud Hook - Load all events
 *
 * PHP Version 5
 * LICENSE: This source file is subject to LGPL license that is available
 * through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author      Ushahidi Team <team(at)ushahidi.com>
 * @package     Ushahidi - http://source.ushahididev.com
 * @copyright   Ushahidi - http://www.ushahidi.com
 * @license     http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
 
 class categorycloud {
     private $max_font_size = 36;   // Maximum font size
     private $min_font_size = 8;   // Minimum font size
     
     /**
      * Registers the main event add method
      */
      public function __construct()
      {
          // Hook into routing
          Event::add('system.pre_controller', array($this, 'add'));
      }
      
      /**
       * Adds all the events to the main Ushahidi application
       */
      public function add()
      {
          if (Router::$controller == 'main')
          {
              // Add stylesheet
              plugin::add_stylesheet('categorycloud/views/css/categorycloud');
              
              Event::add('ushahidi_action.main_sidebar', array($this, '_generate_cloud'));
          }
      }
      
      /**
       * Generates the category tag cloud
       */
      public function _generate_cloud()
      {
          // SQL query to generate the categories
          $query = "SELECT c.id, c.category_title, c.category_color, SUM(c1.incident_count) tag_frequency
                    FROM
                      (SELECT ic.category_id, ifnull(c.parent_id,0) parent_id, COUNT(ic.incident_id) incident_count
                       FROM incident_category ic
                       LEFT JOIN category c ON (ic.category_id = c.id)
                       LEFT JOIN incident i ON (ic.incident_id = i.id)
                       WHERE i.incident_active = 1
                       GROUP BY ic.category_id
                      ) c1
                    LEFT JOIN category c ON (c1.parent_id = c.id OR (c.parent_id = 0 AND c1.category_id = c.id))
                    GROUP BY c.id
                    ORDER BY 2 ASC";
        
          // Instantiate the database
          $db = new Database();
          
          // Execute the query
          $category_items = $db->query($query);
          
          $min_frequency = -1; // Minimum tag frequency
          $max_frequency = -1; // Maximum tag frequency
          
          // Determine the maximum and minimum tag frequencies
          foreach ($category_items as $item)
          {
              // Get the current tag frequency
              $frequency = (int)$item->tag_frequency;
              
              if ($frequency < $min_frequency AND $min_frequency > 0 AND $min_frequency < $max_frequency)
              {
                  $min_frequency = $frequency;
              }
              
              if ($frequency > $min_frequency AND $min_frequency < 0)
              {
                  // Frequency values not set therefore initialize
                  $min_frequency = $frequency;
                  $max_frequency = $min_frequency;
              }
              
              // Set the maximum frequency
              $max_frequency = ($frequency > $max_frequency AND $max_frequency > 0 AND $frequency > $min_frequency)
                  ? $frequency
                  : $max_frequency;
          }
                    
          // Load the tag could view
          $view =  View::factory('categorycloud/tagcloud');
          $view->cloud_title = 'Category Cloud';

          $tag_cloud_items = array();
          foreach ($category_items as $item)
          {
              // Get the current frequency
              $frequency = (int)$item->tag_frequency;
              
              // Get the font size of the cloud item
              $font_size = ($this->_calculate_font_size($frequency, $max_frequency, $min_frequency));
              
              // Populate the $tag_cloud_items array
              $tag_cloud_items[] = array(
                    'id' => $item->id,
                    'category_title' => $item->category_title,
                    'css' => 'color: #'.$item->category_color.'; font-size: '.$font_size.'px;'
                  );
          }
          
          // Set the tag cloud items
          $view->cloud_items = $tag_cloud_items;
          
          $view->render(TRUE);
      }
      
      /**
       * Calculates the font size of a tag cloud item
       */
      private function _calculate_font_size($cur_frequency, $max_frequency, $min_frequency)
      {
          if ($max_frequency - $min_frequency === 0)
          {
                return $this->min_font_size;
          }

          // Calculate the weight using a power law - consider switching to a logarithmic one
          $weight = ($cur_frequency - $min_frequency)/($max_frequency - $min_frequency);
          
          return $this->min_font_size + num::round(($this->max_font_size - $this->min_font_size) * $weight);
      }
 }
 
 new categorycloud;