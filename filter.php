<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * add mambo widgets
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package: filter_mambo
 * @copyright 2015 MoodleFreak.com
 * @author    Luuk Verhoeven
 **/
defined('MOODLE_INTERNAL') || die();

class filter_mambo extends moodle_text_filter {


    /**
     * Override this function to actually implement the filtering.
     *
     * @param $text          some HTML content.
     * @param array $options options passed to the filters
     *
     * @return the HTML content after the filtering has been applied.
     */
    public function filter($text, array $options = array()) {


        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        if (stripos($text, '[mambo-widget-') === false) {
            return $text;
        }


        // we found a widget?
        if (preg_match_all('/\[mambo-widget-+\d+\]/', $text, $matches)) {

            foreach ($matches[0] as $match) {
                $id = str_replace(array('[mambo-widget-' , ']'), '', $match);
                if(is_numeric($id)){

                    // add a mambo widget
                    $widget = $this->getWidgetById($id);
                    if($widget){

                        // need mambo lib and helper
                        require_once __DIR__. '/../../blocks/mambo/locallib.php';

                        // load init if not already done
                        block_mambo_add_widget_init();

                        $text = str_replace($match, $widget, $text);
                    }
                }
            }
        }

        return $text;
    }

    /**
     * get a widget by ID
     * @param int $id
     * @global moodle_database $DB
     * @return bool
     */
    protected function getWidgetById($id = 0)
    {
        global $DB;
        if($this->hasMamboInstalled()) {

            $row = $DB->get_record('mambo_widget' , array('id' => $id));

            if($row){
                $identifier = uniqid();

                $return = '<script>var mamboCallbacks = window.mamboCallbacks || []; '. PHP_EOL;
                $return .= 'mamboCallbacks.push(function() {'. PHP_EOL;
                $return .= 'Mambo( \'#mambo_widget_'.$identifier.'\' )';
                $return .= trim($row->widget). PHP_EOL;
                $return .= '});//close push'. PHP_EOL;
                $return .= '</script>' . PHP_EOL;
                $return .= '<div id="mambo_widget_' . $identifier . '" style="min-height:50px"></div>'. PHP_EOL;

                return $return;
            }
        }
        return false;
    }

    /**
     * validate that mambo is installed
     * @global moodle_database $DB
     * @return bool
     * @throws ddl_exception
     */
    protected function hasMamboInstalled()
    {
        global $DB;
        static $result;

        $dbman = $DB->get_manager();

        if(is_null($result))
        {
            $result = $dbman->table_exists('mambo_widget');
        }
        return $result;
    }

}