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

    public function get($id) {
        $result = parent::get($id);
        $gmc = new GradeModelComponents();
        $gct = new GradeComponentsTemplate();
        $gmc_array = Database::select('grade_components_id', $gmc->table, array(
            'where' => array(
                'grade_model_id = ? ORDER BY grade_components_id ASC LIMIT 1' => $id)));
        $gc_id = $gmc_array[0]['grade_components_id'];
        $gct_array = Database::select(
            'grade_template_id',
            $gct->table,
            array(
                'where' => array(
                    'grade_components_id = ? LIMIT 1' => $gc_id)));
        $result['grade_template_id'] = intval(current($gct_array)['grade_template_id']);
        return $result;

    }

    /**
     * Displays the title + grid
     */
    public function display() {
        // action links
        echo '<div class="actions" style="margin-bottom:20px">';
        if (!empty($_REQUEST)) {
            if (empty($_REQUEST['origin'])) {
                $back_href ='grade_models.php';
            } elseif (strcmp($_REQUEST['origin'],'grade_models') === 0) {
                $back_href ='grade_models.php';
            } elseif (strcmp($_REQUEST['origin'],'grade_template') === 0) {
                $back_href ='grade_template.php';
            }
        } else {
            $back_href ='index.php';
        }

        echo '<a href="'.$back_href.'">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add.png',get_lang('Add'),'','32').'</a>';
        echo '<a href="grade_template.php?origin=grade_models">'.Display::return_icon('stylesheets.png',get_lang('GradeTemplate'),'','32').'</a>';
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

        $gt = new GradeTemplate();
        $gt->fill_grade_model_select_in_form($form, 'grade_template_id');

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

        if (is_array($components)) {
            foreach ($components as $component) {
                if (empty($component['acronym']) && $component['count_elements'] == 0 && (strpos(strtoupper($component['title']), 'COURSE') !== false) ) {
                    $nr_items--;
                }
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

            $gt->fill_grade_model_select_in_form($form,'components['.$i.'][grade_template_id]');
            
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
            ' {label} &nbsp {element} <!-- BEGIN error --> <span class="form_error">{error}</span><!-- END error -->
             <a href="javascript:plusItem(' . ($counter+1) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="plus-' . ($counter+1) . '" src="../img/icons/22/add.png" alt="'.get_lang('Add').'" title="'.get_lang('Add').'"></img>
            </a>
            <a href="javascript:minItem(' . ($counter) . ')">
                <img style="display: '.(($counter>=$nr_items)?'inline':'none').';" id="min-' . $counter . '" src="../img/delete.png" alt="'.get_lang('Delete').'" title="'.get_lang('Delete').'"></img>
            </a>            
            </div></p></div>';

            $template_count_elements =
                '<!-- BEGIN required -->'. get_lang('Evaluations').
                '&nbsp {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $template_exclude_elements =
                '<!-- BEGIN required -->'. get_lang('Exclusions').
                '&nbsp {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $renderer->setElementTemplate($template_acronym, 'components['.$i.'][title]');
            $renderer->setElementTemplate($template_percentage ,  'components['.$i.'][percentage]');
            $renderer->setElementTemplate($template_acronym , 'components['.$i.'][acronym]');
            $renderer->setElementTemplate($template_title, 'components['.$i.'][grade_template_id]');

            $renderer->setElementTemplate($template_acronym , 'components['.$i.'][prefix]');
            $renderer->setElementTemplate($template_exclude_elements , 'components['.$i.'][exclusions]');
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
            $gmc_ids = $gmc->get_gmc_ids($id);
            $components = array();
            foreach ($gmc_ids as $key => $value) {
                $component = $gmc->get_components($key);
                $components = array_merge($components, $component);
            }
            return $components;
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
                    'grade_abstract_model_id' => $params['grade_abstract_model_id'],
                    'grade_template_id' => $params['grade_template_id'],
                ));
            $gmc_params['grade_components_id'] = $component_parent_id;
            $gmc->save($gmc_params);
            $i = 0;
            foreach ($params['components'] as $component) {
                $i++;
                if (!empty($component['title']) && !empty($component['percentage']) && !empty($component['acronym'])) {
                    $component['parent_id'] = $component_parent_id;
                    $component['grade_abstract_model_id'] = $params['grade_abstract_model_id'];
                    $component_id = $gc->save($component);
                    if (!empty($component_id)) {
                        $gct = new GradeComponentsTemplate();
                        $gmc_params['grade_components_id'] = $component_parent_id;
                        $gmc_params['grade_template_id'] = $component['grade_template_id'];
                        $gmc_params['grade_components_element'] = $i;
                        $gct->save($gmc_params);
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
                if (!empty($params['id']) && is_array($params['components'])) {
                    $gc = new GradeComponents();
                    $gmc = new GradeModelComponents();
                    $gct = new GradeComponentsTemplate();
                    $temp = $gc->get(current($params['components'])['id']);
                    $course_component = array(
                        'id' => $temp['parent_id'],
                        'grade_components_id' => $temp['parent_id'],
                        'grade_template_id' => $params['grade_template_id'],
                    );
                    $res = Database::select('id', $gct->table, array(
                        'where' => array(
                            'grade_components_id = ? ORDER BY grade_components_element ASC'
                            => $course_component['id'])));
                    if (!empty($res)) {
                        $gc->update($course_component);
                    } else {
                        $gct->save($course_component);
                    }
                    $i = 0;
                    foreach ($params['components'] as $component) {
                        $i++;
                        $gc->get($component['parent_id']);
                        if (!empty($component['id'])){
                            if (empty($component['title']) && empty($component['percentage']) && empty($component['acronym'])) {
                                $gc->delete($component['id']);
                            } else {
                                $gc->update($component);
                                $gct_row = array(
                                    'id' => $res[$i]['id'],
                                    'grade_template_id' => $component['grade_template_id'],
                                );
                                $gct->update($gct_row);
                            }
                        } else {
                            if (empty($component['title']) && empty($component['percentage']) && empty($component['acronym'])) {
                                // nothing to do
                            } else {
                                $component['grade_abstract_model_id'] = $temp['grade_abstract_model_id'];
                                $component['parent_id'] = $temp['parent_id'];
                                $gc_id = $gc->save($component);
                                $gmc_row = array(
                                    'grade_model_id' => $params['id'],
                                    'grade_components_id' => $gc_id,
                                    'grade_template_id' => $component['template_id'],
                                    'grade_components_element' => $i
                                );
                                $gmc->save($gmc_row);
                                $gmc_row['grade_components_id'] = $temp['parent_id'];
                                $gct->save($gmc_row);
                            }
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
        $gmc = new GradeModelComponents();
        $gc = new GradeComponents();
        $gmc_array = $gmc->get_all(array('where'=>array('grade_model_id = ?' => $id)));
        foreach ($gmc_array as $row) {
            $gc->delete($row['grade_components_id']);
            $gmc->delete($row['id']);
        }

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
                $gmc = new GradeModelComponents();
                foreach ($grade_models as $item) {
                    $id = $gmc->get_course_model_components_id($item['id']);
                    $grade_model_options[$id] = $item['name'];
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
    public function get_components($id) {
        if (!empty($id)) {
            $result = Database::select('grade_components_id',$this->table,array('where' => array('id = ?' => $id)));
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
                $gct = new GradeComponentsTemplate();
                $components = $gc->get_all(array('where'=> array('id IN '.$gc_set.' AND id != ?' => '0')));
                $components[key($components)]['gmc_id'] = $id;
                $elements = $ge->get_all(array('where'=> array('grade_components_id IN '.$gc_set.' AND id != ?' => '0')));
                $templates = $gct->get_all(array('where'=> array('grade_components_id IN '.$gc_set.' AND id != ?' => '0')));
                $elements = array_merge($elements,$templates);
                foreach ($elements as $element) {
                    $components[$element['grade_components_id']] = array_merge($components[$element['grade_components_id']], $element);
                }
                return $components;
            }
        }
        return null;
    }

    public function get_course_model_components_id($model_id) {
        $result = Database::select('id', $this->table, array('where' => array('grade_model_id = ? ORDER BY id ASC LIMIT 1' => $model_id)));
        return key($result);
    }

    public function get_gmc_ids($model_id) {
        $result = Database::select('id',$this->table,array('where' => array('grade_model_id = ?' => $model_id)));
        return $result;
    }

    public function get_model_id($id) {
        $result = Database::select('grade_model_id',$this->table,array('where' => array('id = ?' => $id)));
        return current($result)['grade_model_id'];
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
        $params['grade_components_id'] = $id;
        if (!empty($id) && !empty($params['acronym'])) {
            $ge = new GradeElements();
            $ge->save($params);
        }
        if (!empty($id) && !empty($params['grade_template_id'])) {
            $gct = new GradeComponentsTemplate();
            $gct->save($params);
        }
        return $id;
    }

    public function update($params) {
        parent::update($params);
        $ge = new GradeElements();
        $gct = new GradeComponentsTemplate();
        $params['grade_components_id'] = $params['id'];
        $ge_array = $ge->get_all(array('where' => array('grade_components_id = ?' => $params['id'])));
        foreach ($ge_array as $row) {
            $params['id'] = $row['id'];
            $ge->update($params);
        }
        $gct_array = $gct->get_all(array('where' => array('grade_components_id = ?' => $params['id'])));
        foreach ($gct_array as $row) {
            $params['id'] = $row['id'];
            $ge->update($params);
        }
    }

    public function delete ($id) {
        $ge = new GradeElements();
        $ge_array =  $ge->get_all(array('where' => array('grade_components_id = ?' => $id)));
        foreach ($ge_array as $row) {
            $ge->delete($row['id']);
        }
        parent::delete($id);
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

class GradeTemplate extends Model {
    var $table;
    var $columns = array('id','name','description','score_color_percent');

    public function __construct() {
        $this->table = Database::get_main_table(TABLE_GRADE_TEMPLATE);
    }

    public function get_components($id){
        $gsdt = new GradeScoreDisplayTemplate();
        return $id? $gsdt->get_all(array('where' => array('grade_template_id = ?' => $id))) : array();
    }

    /**
     * Displays the title + grid
     */
    public function display() {
        // action links
        echo '<div class="actions" style="margin-bottom:20px">';
        if (!empty($_REQUEST)) {
            if (empty($_REQUEST['origin'])) {
                $back_href ='grade_template.php';
            } elseif (strcmp($_REQUEST['origin'],'grade_models') === 0) {
                $back_href ='grade_models.php';
            } elseif (strcmp($_REQUEST['origin'],'grade_template') === 0) {
                $back_href ='grade_template.php';
            }
        } else {
            $back_href ='index.php';
        }
        echo '<a href="'.$back_href.'">'.Display::return_icon('back.png',get_lang('Back'),'','32').'</a>';
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add.png',get_lang('Add'),'','32').'</a>';
        echo '<a href="grade_models.php?origin=grade_template">'.Display::return_icon('gradebook.png',get_lang('GradeModel'),'','32').'</a>';
        echo '</div>';
        echo Display::grid_html('grade_template');
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

        $form->addElement('text', 'score_color_percent', get_lang('ScoreColorPercent'), array('size' => '40'));

        $form->addRule('score_color_percent', get_lang('OnlyNumbers'), 'numeric');

        $form->addElement('hidden', 'maxvalue', '100');
        $form->addElement('hidden', 'minvalue', '0');

        $form->addRule(array('score_color_percent', 'maxvalue'), get_lang('Over100'), 'compare', '<=');
        $form->addRule(array('score_color_percent', 'minvalue'), get_lang('UnderMin'), 'compare', '>=');

        $form->addElement('label', get_lang('Components'));

        //Get components
        $nr_items = 1;
        $max      = 10;

        // Setting the defaults

        $defaults = $this->get($id);
        $components = $this->get_components($defaults['id']);
        if ($action == 'edit') {
            if (!empty($components)) {
                $nr_items = max(count($components) - 1, $nr_items);
            }
        }

        $renderer = & $form->defaultRenderer();

        $component_array = array();

        for ($i = 0; $i <= $max;  $i++) {
            $counter = $i;
            $form->addElement('text', 'components['.$i.'][score]', null, array('class' => 'span1', 'placeholder' => get_lang('Score')));
            $form->addElement('text', 'components['.$i.'][display]',    null, array('class' => 'span1', 'placeholder' => get_lang('Display')));

            $form->addElement('hidden', 'components['.$i.'][id]');

            $template_percentage =
                '<div id=' . $i . ' style="display: '.(($i<=$nr_items)?'inline':'none').';" class="control-group">
                <p>
                <label class="control-label">{label}</label>
                <div class="controls">
                    '.get_lang('LessThan').' {element} <!-- BEGIN error --><span class="form_error">{error}</span><!-- END error --> % = ';

            $template_acronym = '
            <!-- BEGIN required -->
            {element} {label} <!-- BEGIN error --><span class="form_error">{error}</span> <!-- END error -->';

            $template_title =
                ' '. get_lang('Display') .'&nbsp {element} <!-- BEGIN error --> <span class="form_error">{error}</span><!-- END error -->
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

            $renderer->setElementTemplate($template_percentage ,  'components['.$i.'][score]');
            $renderer->setElementTemplate($template_title , 'components['.$i.'][display]');

            if ($i == 0) {
                $form->addRule('components['.$i.'][score]', get_lang('ThisFieldIsRequired'), 'required');
                $form->addRule('components['.$i.'][display]', get_lang('ThisFieldIsRequired'), 'required');
            }
            $form->addRule('components['.$i.'][score]', get_lang('OnlyNumbers'), 'numeric');

            $form->addRule(array('components['.$i.'][score]', 'maxvalue'), get_lang('Over100'), 'compare', '<=');
            $form->addRule(array('components['.$i.'][score]', 'minvalue'), get_lang('UnderMin'), 'compare', '>=');

            $component_array[] = 'components['.$i.'][score]';
        }

        if ($action == 'edit') {
            $form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
            $form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="save"');
        }

        if (!empty($components)) {
            $counter = 0;
            foreach ($components as &$component) {
                if (empty($component['display']) && $component['score'] == 0) {
                    continue;
                }
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

    public function fill_grade_model_select_in_form(&$form, $name = 'grade_template_id', $default_value = null) {
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
            $form->addElement('select', $name, get_lang('Template'), $grade_model_options);
            $default_platform_setting = api_get_setting('gradebook_default_grade_template_id');

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

    public function save($params) {
        $res['id'] = parent::save($params);
        if(is_array($params['components'])) {
            $gsdt = new GradeScoreDisplayTemplate();
            foreach ($params['components'] as $component) {
                if (!empty($component['display'])) {
                    $component['grade_template_id'] = $res['id'];
                    $res['components'][] = $gsdt->save($component);
                }
            }
        }
        return $res;
    }

    public function update($params) {
        parent::update($params);
        if(is_array($params['components'])) {
            $gsdt = new GradeScoreDisplayTemplate();
            foreach ($params['components'] as $component) {
                if (!empty($component['id'])) {
                    if (!empty($component['display'])){
                        $gsdt->update($component);
                    } else {
                        $gsdt->delete($component['id']);
                    }
                } elseif(!empty($component['display'])) {
                    $component['grade_template_id'] = $params['id'];
                    $gsdt->save($component);
                }
            }
        }
    }

    public function delete($id) {
        $gsdt = new GradeScoreDisplayTemplate();
        $gct = new GradeComponentsTemplate();

        $gsdt_array = $gsdt->get_all(array('where'=>array('grade_template_id = ?' => $id)));
        foreach ($gsdt_array as $row) {
            $gsdt->delete($row['id']);
        }

        $gct_array = $gct->get_all(array('where'=>array('grade_template_id = ?' => $id)));
        foreach ($gct_array as $row) {
            $gct->delete($row['id']);
        }
        parent::delete($id);
        //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }
}

class GradeScoreDisplayTemplate extends Model {
    var $table;
    var $columns = array('id', 'score', 'display', 'grade_template_id');

    public function __construct() {
        $this->table = Database::get_main_table(TABLE_GRADE_SCORE_DISPLAY_TEMPLATE);
    }

}

class GradeComponentsTemplate extends Model {
    var $table;
    var $columns = array('id', 'grade_components_id', 'grade_template_id', 'grade_components_element');

    public function __construct() {
        $this->table = Database::get_main_table(TABLE_GRADE_COMPONENTS_TEMPLATE);
    }
}