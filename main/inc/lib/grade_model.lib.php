<?php
/* For licensing terms, see /license.txt */
/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/
/**
 * Code
 */
/**
 * @package chamilo.library
 */

require_once 'fckeditor/fckeditor.php';

class GradeModel extends Model {
    
    var $table;
    var $columns = array('id', 'name', 'description', 'created_at', 'grade_abstract_model_id');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_MODEL);
	}    
    
    public function get_all($where_conditions = array()) {
        return Database::select('*',$this->table, array('where'=>$where_conditions,'order' =>'name ASC'));
    }
    
    public function get_count() {        
        $row = Database::select('count(*) as count', $this->table, array(),'first');
        return $row['count'];
    }    
    
    /**
     * Displays the title + grid
     */
	public function display() {
		// action links
		echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="grade_models.php">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';
		echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add.png',get_lang('Add'),'','32').'</a>';        				
		echo '</div>';   
        echo Display::grid_html('grade_model');  
	}
    
    /**
     * Returns a Form validator Obj
     * @todo the form should be auto generated
     * @param   string  url
     * @param   string  action add, edit
     * @return  obj     form validator obj 
     */
    public function return_form($url, $action) {
		
		$oFCKeditor = new FCKeditor('description') ;
		$oFCKeditor->ToolbarSet = 'grade_model';
		$oFCKeditor->Width		= '100%';
		$oFCKeditor->Height		= '200';
		$oFCKeditor->Value		= '';
		$oFCKeditor->CreateHtml();
		
        $form = new FormValidator('grades', 'post', $url);
        
        // Settting the form elements
        $header = get_lang('Add');
        
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);
        
        $form->addElement('text', 'name', get_lang('Name'), array('size' => '70'));
        $form->add_html_editor('description', get_lang('Description'), false, false, array('ToolbarSet' => 'careers','Width' => '100%', 'Height' => '250'));	   

        $form->addElement('label', get_lang('Components'));
                
        //Get components
        $nr_items = 2;
        $max      = 10;
                
        // Setting the defaults
        
        $defaults = $this->get($id);
        $components = $this->get_components($defaults['id']);
        if ($action == 'edit') {
            if (!empty($components)) { 
                $nr_items = count($components) -1;
            }
        }        

        foreach ($components as $component) {
            if (empty($component['acronym']) && $component['count_elements'] == 0 && (strpos(strtoupper($component['title']), 'COURSE') !== false) ) {
                $nr_items--;
            }
        }

        $form->addElement('hidden', 'maxvalue', '100');
		$form->addElement('hidden', 'minvalue', '0');
                
        $renderer = & $form->defaultRenderer();
        
        $component_array = array();

        for ($i = 0; $i <= $max;  $i++) {
            $counter = $i;
            $form->addElement('text', 'components['.$i.'][percentage]', null, array('class' => 'span1'));
            $form->addElement('text', 'components['.$i.'][acronym]',    null, array('class' => 'span1', 'placeholder' => get_lang('Acronym')));
            $form->addElement('text', 'components['.$i.'][title]',      null, array('class' => 'span2', 'placeholder' => get_lang('Description')));
            $form->addElement('text', 'components['.$i.'][prefix]',      null, array('class'=> 'span1', 'placeholder' => get_lang('Prefix')));

            $options = array(0=>0, 1 => 1, 2 => 2, 3=>3, 4=> 4, 5=> 5);
            $form->addElement('select', 'components['.$i.'][count_elements]', null, $options);
            
            $options = array(0=>0, 1 => 1, 2 => 2, 3=>3, 4=> 4, 5=> 5);            
            $form->addElement('select', 'components['.$i.'][exclusions]',      null, $options);
            
            
            
            $form->addElement('hidden', 'components['.$i.'][id]');
            
            $template_percentage =
            '<div id=' . $i . ' style="display: '.(($i<=$nr_items)?'inline':'none').';" class="control-group">                
                <p>
                <label class="control-label">{label}</label>
                <div class="controls">                    
                    {element} <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --> % = ';
            
            $template_acronym = '
            <!-- BEGIN required -->      
            {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $template_title =
            ' '. get_lang('QuantityToExclude') .'&nbsp {element} <!-- BEGIN error --> <span class="form_error">{error}</span><!-- END error -->
             <a href="javascript:plusItem(' . ($counter+1) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="plus-' . ($counter+1) . '" src="../img/icons/22/add.png" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img>
            </a>
            <a href="javascript:minItem(' . ($counter) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="min-' . $counter . '" src="../img/delete.png" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img>
            </a>            
            </div></p></div>';

            $template_count_elements =
                '<!-- BEGIN required --> '. get_lang('Quantity') .
                '&nbsp {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $renderer->setElementTemplate($template_acronym, 'components['.$i.'][title]');
            $renderer->setElementTemplate($template_percentage ,  'components['.$i.'][percentage]');
            $renderer->setElementTemplate($template_acronym , 'components['.$i.'][acronym]');

            $renderer->setElementTemplate($template_acronym , 'components['.$i.'][prefix]');
            $renderer->setElementTemplate($template_title , 'components['.$i.'][exclusions]');
            $renderer->setElementTemplate($template_count_elements , 'components['.$i.'][count_elements]');

            if ($i == 0) {
                $form->addRule('components['.$i.'][percentage]', get_lang('ThisFieldIsRequired'), 'required');
                $form->addRule('components['.$i.'][title]', get_lang('ThisFieldIsRequired'), 'required');
                $form->addRule('components['.$i.'][acronym]', get_lang('ThisFieldIsRequired'), 'required');                
            }
            $form->addRule('components['.$i.'][percentage]', get_lang('OnlyNumbers'), 'numeric');
            
            $form->addRule(array('components['.$i.'][percentage]', 'maxvalue'), get_lang('Over100'), 'compare', '<=');
            $form->addRule(array('components['.$i.'][percentage]', 'minvalue'), get_lang('UnderMin'), 'compare', '>=');   
            
            $component_array[] = 'components['.$i.'][percentage]';
        }
        
        //New rule added in the formvalidator compare_fields that filters a group of fields in order to compare with the wanted value
        $form->addRule($component_array, get_lang('AllMustWeight100'), 'compare_fields', '==@100');   
                
        $form->addElement('advanced_settings', get_lang('AllMustWeight100'));
        	            
        if ($action == 'edit') {
        	$form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
        	$form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="save"');
        }

        if (!empty($components)) {
            $counter = 0;
            foreach ($components as &$component) {
                if (empty($component['acronym']) && $component['count_elements'] == 0 && (strpos(strtoupper($component['title']), 'COURSE') !== false) ) {
                    continue;
                }
                $component['id'] = $component['grade_components_id'];
                foreach ($component as $key => $value) {
                    $defaults['components['.$counter.']['.$key.']'] = $value;
                }
                $counter++;
            }
        }
        $form->setDefaults($defaults);
    
        // Setting the rules
        $form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');               
		return $form;                                
    }
    
    public function get_components($id) {

        if (!empty($id)) {
            $gmc = new GradeModelComponents();
            return $gmc->get_components($id);
        }
        return null;
    }
        
    public function save($params, $show_query = false) {
	    $id = parent::save($params, $show_query);
	    if (!empty($id)) {
            $gmc = new GradeModelComponents();
            $gc = new GradeComponents();
            $gmc_params = array('grade_model_id' => $id);
            $component_parent_id = $gc->save(array(
                    'title' => 'COURSE',
                    'percentage' => 0,
                    'grade_model_abstract_id' => $params['grade_abstract_model_id'],
                ));
            $gmc_params['grade_components_id'] = $component_parent_id;
            $gmc->save($gmc_params);
            foreach ($params['components'] as $component) {                
                if (!empty($component['title']) && !empty($component['percentage']) && !empty($component['acronym'])) {
                    $obj = new GradeComponents();
                    $gmc = new GradeModelComponents();
                    $component['parent_id'] = $component_parent_id;
                    $component['grade_abstract_model_id'] = $params['grade_abstract_model_id'];
                    $component_id = $obj->save($component);
                    if (!empty($component_id)) {
                        $gmc_params['grade_components_id'] = $component_id;
                        $gmc->save($gmc_params);
                    }
                }
            }                            
        }
        //event_system(LOG_CAREER_CREATE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());   		
   		return $id;
    }

    /**
     * @param $params
     * @param int $mode 0:Update Grade model, components, elements and methods
     *                  1:Clone with changes Grade model, components, elements and methods
     * @return bool|void
     */
    public function update($params, $mode = 1) {
        switch ($mode) {
            case 0:
                parent::update($params);
                if (!empty($params['id'])) {
                    foreach ($params['components'] as $component) {
                        $obj = new GradeComponents();
                        if (empty($component['title']) && empty($component['percentage']) && empty($component['acronym'])) {
                            $obj->delete($component['id']);
                        } else {
                            $obj->update($component);
                        }
                    }
                }
                break;
            case 1:
                $base_gm = $this->get($params['id']);
                //$params = array_merge($base_gm, $base_gm);
                $params['id'] = '';
                $params['grade_abstract_model_id'] = $base_gm['grade_abstract_model_id'];
                foreach($params['components'] as &$component) {
                    $component['id'] = '';
                }
                $id = $this->save($params);
                break;
        }
    }
    
    public function delete($id) {
	    parent::delete($id);
	    //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }
    
    public function fill_grade_model_select_in_form(&$form, $name = 'gradebook_model_id', $default_value = null) {
        if (api_get_setting('gradebook_enable_grade_model') == 'false') {
            return false;
        }            
            
        if (api_get_setting('teachers_can_change_grade_model_settings') == 'true' || api_is_platform_admin()) {
            $grade_models = $this->get_all();                
            $grade_model_options = array('-1' => get_lang('None'));            
            if (!empty($grade_models)) {
                foreach ($grade_models as $item) {
                    $grade_model_options[$item['id']] = $item['name'];
                }                
            }
            $form->addElement('select', $name, get_lang('GradeModel'), $grade_model_options);
            $default_platform_setting = api_get_setting('gradebook_default_grade_model_id');
            
            $default = -1;
            
            if ($default_platform_setting == -1) {
                if (!empty($default_value)) {
                    $default = $default_value;
                }                
            } else {
                $default = $default_platform_setting;
            }
            
            if (!empty($default) && $default != '-1') {
                $form->setDefaults(array($name => $default));
            }
        }
    }
}

class GradeModelComponents extends Model {
    var $table;
    var $columns = array('id', 'grade_components_id', 'grade_model_id');

    public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_MODEL_COMPONENTS);
    }
    public function save($params, $show_query = false) {
        $id = parent::save($params, $show_query);
        return $id;
    }
    public function get_components($model_id) {
        if (!empty($model_id)) {
            $result = Database::select('grade_components_id',$this->table,array('where'=> array('grade_model_id = ?' => $model_id)));
            if (!empty($result)) {
                $gc_set = '(';
                foreach ($result as $key => $res) {
                    if ($key == 0) {
                        //nothing to do
                    } else {
                        $gc_set .= ', ';
                    }
                    $gc_set .= current($res);
                }
                $gc_set .= ')';
                $gc = new GradeComponents();
                $ge = new GradeElements();
                $components = $gc->get_all(array('where'=> array('id IN '.$gc_set.' AND id != ?' => '0')));
                $elements = $ge->get_all(array('where'=> array('grade_components_id IN '.$gc_set.' AND id != ?' => '0')));
                foreach ($elements as $element) {
                    $components[$element['grade_components_id']] = array_merge($components[$element['grade_components_id']], $element);
                }
                return $components;
            }
        }
        return null;
    }
}

class GradeComponents extends Model {
    var $table;
    var $columns = array('id', 'title', 'percentage', 'description', 'grade_abstract_model_id', 'parent_id');
    
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_COMPONENTS);
	}

    public function save($params, $show_query = false) {        
	    $id = parent::save($params, $show_query);
        if (!empty($id) && !empty($params['acronym'])) {
            $ge = new GradeElements();
            $params['grade_components_id'] = $id;
            $ge->save($params);
        }
        return $id;
    }

    public function update($params) {
        parent::update($params);
        $ge = new GradeElements();
        $ge_all = $ge->get_all(array('where' => array('grade_components_id = ?' => $params['id'])));
        $params['grade_components_id'] = $params['id'];
        foreach ($ge_all as $ge_item) {
            $params['id'] = $ge_item['id'];
            $ge->update($params);
        }
    }
}

class GradeElements extends Model {
    var $table;
    var $columns = array('id','acronym','description','type','prefix','count_elements','exclusions','grade_components_id');

    public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_ELEMENTS);
    }
}

class GradeAbstractModel extends Model {
    var $table;
    var $columns = array('id', 'name', 'description', 'created_at');

    public function __construct() {
        $this->table =  Database::get_main_table(TABLE_GRADE_ABSTRACT_MODEL);
    }
}