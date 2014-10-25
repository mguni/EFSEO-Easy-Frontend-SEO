<?php
/**
 * EFSEO - Easy Frontend SEO for Joomal! 3.x
 * License: GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * Author: Viktor Vogel
 * Project page: http://joomla-extensions.kubik-rubik.de/efseo-easy-frontend-seo
 *
 * @license GNU/GPL
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');

class EasyFrontendSeoModelEntry extends JModelLegacy
{
    protected $_data = null;
    protected $_id = null;
    protected $_input;
    protected $_error;

    function __construct()
    {
        parent::__construct();

        $this->_input = JFactory::getApplication()->input;

        $array = $this->_input->get('id', 0, 'ARRAY');
        $this->setId((int)$array[0]);
    }

    function getCharactersLength()
    {
        $plugin = JPluginHelper::getPlugin('system', 'easyfrontendseo');

        if(!empty($plugin))
        {
            $plugin_params = new JRegistry();
            $plugin_params->loadString($plugin->params);

            $characters_length = array('title' => $plugin_params->get('characters_title'), 'description' => $plugin_params->get('characters_description'));
        }
        else
        {
            $characters_length = array('title' => 65, 'description' => 160);
        }

        return $characters_length;
    }

    function setId($id)
    {
        $this->_id = $id;
        $this->_data = null;
    }

    function getId()
    {
        return $this->_id;
    }

    function getError($i = null, $toString = true)
    {
        return $this->_error;
    }

    function getData()
    {
        if($this->state->get('task') != 'add')
        {
            $this->_data = $this->getInputSession();

            if(empty($this->_data))
            {
                $query = "SELECT * FROM ".$this->_db->quoteName('#__plg_easyfrontendseo')." WHERE ".$this->_db->quoteName('id')." = ".$this->_db->quote($this->_id);
                $this->_db->setQuery($query);
                $this->_data = $this->_db->loadObject();

                if(empty($this->_data))
                {
                    $this->_data = $this->getTable('entry', 'EasyFrontendSeoTable');
                    $this->_data->id = 0;
                }
            }
        }
        else
        {
            $this->_data = $this->getTable('entry', 'EasyFrontendSeoTable');
            $this->_data->id = 0;
        }

        return $this->_data;
    }

    function store()
    {
        $row = $this->getTable('entry', 'EasyFrontendSeoTable');
        $data = array();

        // Get entered data
        $data['id'] = $this->_input->get('id', '', 'INT');
        $data['url'] = trim($this->_input->get('url', '', 'STRING'));
        $data['title'] = trim($this->_input->get('title', '', 'STRING'));
        $data['description'] = trim(stripslashes(preg_replace('@\s+(\r\n|\r|\n)@', ' ', $this->_input->get('description', '', 'STRING'))));
        $data['keywords'] = trim($this->_input->get('keywords', '', 'STRING'));
        $data['generator'] = trim($this->_input->get('generator', '', 'STRING'));
        $data['robots'] = trim($this->_input->get('robots', '', 'STRING'));

        // Do not save same URLs more than once
        if($this->checkEntry($data['url']) == false)
        {
            $this->_error = 'duplicate';

            return false;
        }

        if(!$row->save($data))
        {
            $this->setError($this->_db->getErrorMsg());
            $this->_error = 'database';

            return false;
        }

        return true;
    }

    function delete()
    {
        $ids = $this->_input->get('id', 0, 'ARRAY');
        $row = $this->getTable('entry', 'EasyFrontendSeoTable');

        foreach($ids as $id)
        {
            if(!$row->delete($id))
            {
                $this->setError($row->_db->getErrorMsg());

                return false;
            }
        }

        return true;
    }

    function publish($state)
    {
        $id = $this->_input->get('id', 0, 'ARRAY');
        $row = $this->getTable('entry', 'EasyFrontendSeoTable');

        if(!$row->publish($id, $state))
        {
            $this->setError($row->getError());

            return false;
        }

        return true;
    }

    private function checkEntry($url)
    {
        $db = JFactory::getDbo();

        $query = "SELECT * FROM ".$db->quoteName('#__plg_easyfrontendseo')." WHERE ".$db->quoteName('url')." = ".$db->quote($url);
        $this->_db->setQuery($query);
        $row = $this->_db->loadAssoc();

        if(empty($row))
        {
            return true;
        }
        else
        {
            if($row['id'] == $this->_id)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function storeInputSession($input)
    {
        $session = JFactory::getSession();
        $session->set('efseo_data', $input);

        return;
    }

    private function getInputSession()
    {
        $session = JFactory::getSession();
        $input = $session->get('efseo_data');

        if(!empty($input))
        {
            $data = new stdClass();

            $data->id = $input->getInt('id');
            $data->url = trim($input->get('url', '', 'RAW'));
            $data->title = trim($input->get('title', '', 'RAW'));
            $data->description = trim($input->get('description', '', 'RAW'));
            $data->keywords = trim($input->get('keywords', '', 'RAW'));
            $data->generator = trim($input->get('generator', '', 'RAW'));
            $data->robots = trim($input->get('robots', '', 'RAW'));

            $session->clear('efseo_data');

            return $data;
        }
        else
        {
            return false;
        }
    }
}
