<?php
 
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Syllabus extends Student_Controller {

	public function __construct()
    {
        parent::__construct();
    }
    public function index()
    {      
         $this->session->set_userdata('top_menu', 'syllabus');
        

		$monday = strtotime("last monday");
		$monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
		$sunday = strtotime(date("Y-m-d",$monday)." +6 days");
		$this_week_start = date("Y-m-d",$monday);
		$this_week_end = date("Y-m-d",$sunday);
		$data['this_week_start']=date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($this_week_start));
		$data['this_week_end']=date($this->customlib->getSchoolDateFormat(), $this->customlib->dateyyyymmddTodateformat($this_week_end));
        $this->load->view('layout/student/header', $data);
        $this->load->view('user/syllabus/syllabus', $data);
        $this->load->view('layout/student/footer', $data);
    }


    public function get_weekdates(){       
 
        $this_week_start=$_POST['date'];  
        $date = date_create($this_week_start);
        if($_POST['status']=='pre_week'){
           date_add($date, date_interval_create_from_date_string('-6 days'));
           $this_week_end= date_format($date, $this->customlib->getSchoolDateFormat());
           $data['this_week_start']=$this_week_end;
           $data['this_week_end']=$this_week_start;
        }else{            
            date_add($date, date_interval_create_from_date_string('+6 days'));
            $this_week_end= date_format($date, $this->customlib->getSchoolDateFormat());
            $data['this_week_start']=$this_week_start;
            $data['this_week_end']=$this_week_end;
        }  
        
        $this->session->set_userdata('top_menu', 'Time_table');
        $student_current_class = $this->customlib->getStudentCurrentClsSection();
        $student_id       = $this->customlib->getStudentSessionUserID();
        $student          = $this->student_model->get($student_id);
        $days        = $this->customlib->getDaysname();
        $days_record = array();
        foreach ($days as $day_key => $day_value) {         
            $days_record[$day_key] = $this->subjecttimetable_model->getparentSubjectByClassandSectionDay($student_current_class->class_id, $student_current_class->section_id, $day_key);
        }
        $data['timetable'] = $days_record;
        $this->load->view('user/syllabus/_get_weekdates', $data);
    }
	public function get_subject_syllabus(){    
        $data['subject_group_subject_id']	=	$_POST['subject_group_subject_id'];  
        $data['date']					=	date('Y-m-d', strtotime($_POST['new_date']));
		$data['time_from']=$_POST['time_from']; 
        $data['time_to']=$_POST['time_to'];
        $data['subject_group_class_section_id']=$_POST['subject_group_class_section_id'];
        $data['result'] = $this->syllabus_model->get_subject_syllabus_student($data);
       // echo $this->db->last_query();die;
		$this->load->view('user/syllabus/_get_subject_syllabus', $data);
    }

    
     public function download($doc)
    {
        $this->load->helper('download');
        $filepath = "./uploads/syllabus_attachment/" . $this->uri->segment(4);
       // $filepath = $this->uri->segment(4);
        $data     = file_get_contents($filepath);
        $name     = $this->uri->segment(4);
        force_download($name, $data);
    }
  public function lacture_video_download($doc)
    {
        $this->load->helper('download'); 
        $filepath = "./uploads/syllabus_attachment/lacture_video/" . $this->uri->segment(4);
      
        $data     = file_get_contents($filepath);
        $name     = $this->uri->segment(4);
        force_download($name, $data);
    }

    public function status(){
        $this->session->set_userdata('top_menu', 'syllabus/status');
        $student_current_class = $this->customlib->getStudentCurrentClsSection();
        $student_id       = $this->customlib->getStudentSessionUserID();
        $student          = $this->student_model->get($student_id);
        //SELECT subject_group_subjects.id as subject_group_subjects_id,subject_group_class_sections.id as subject_group_class_sections_id FROM `class_sections` join subject_group_class_sections on subject_group_class_sections.class_section_id=class_sections.id join subject_group_subjects on subject_group_subjects.subject_group_id=subject_group_class_sections.subject_group_id WHERE class_sections.class_id='1' and class_sections.section_id=1
        
        $subjects=$this->syllabus_model->getmysubjects($student_current_class->class_id,$student_current_class->section_id);
      
       foreach ($subjects as $key => $value){
        $show_status=0;
        $teacher_summary=array();
        $lesson_result=array();
        $complete=0;
        $incomplete=0;
        $array[]=$value;
         $subject_details=   $this->syllabus_model->get_subjectstatus($value->subject_group_subjects_id,$value->subject_group_class_sections_id);
        if($subject_details[0]->total!=0){

         $complete=($subject_details[0]->complete/$subject_details[0]->total)*100;
         $incomplete=($subject_details[0]->incomplete/$subject_details[0]->total)*100;
         if($value->code==''){
            $lebel=$value->name;
         }else{
            $lebel=$value->name.' ('.$value->code.')';

         }
         $data['subjects_data'][$value->subject_group_subjects_id]=array(
                                        'lebel'=>$lebel,
                                        'complete'=>round($complete),
                                        'incomplete'=>round($incomplete),
                                        'id'=>$value->subject_group_subjects_id.'_'.$value->code,
                                        'total'=>$subject_details[0]->total,
                                        'name'=>$value->name
                                        );
          
       }else{

        $data['subjects_data'][$value->subject_group_subjects_id]=array(
                                        'lebel'=>$value->name.' ('.$value->code.')',
                                        'complete'=>0,
                                        'incomplete'=>0,
                                        'id'=>$value->subject_group_subjects_id.'_'.$value->code,
                                        'total'=>0,
                                        'name'=>$value->name

                                        );
       }

        $syllabus_report=   $this->syllabus_model->get_subjectsyllabussreport($value->subject_group_subjects_id,$value->subject_group_class_sections_id);
      // echo $this->db->last_query();die;
        //print_r($syllabus_report);die;
        $lesson_result=array();
        foreach ($syllabus_report as $syllabus_reportkey => $syllabus_reportvalue) {
            
            $topic_data=array();
            $topic_result=$this->syllabus_model->get_topicbylessonid($syllabus_reportvalue['id']);
            $topic_complete=0;
            foreach ($topic_result as $topic_resultkey => $topic_resultvalue) {
                if($topic_resultvalue['status']==1){
                    $topic_complete++;
                }

                $topic_data[] = array('name' => $topic_resultvalue['name'],'status'=> $topic_resultvalue['status'],'complete_date'=>$topic_resultvalue['complete_date']);
            }
            $total_topic=count($topic_data);
            if($total_topic>0){
                $incomplete_percent=round((($total_topic-$topic_complete)/$total_topic)*100);
            $complete_percent=round(($topic_complete/$total_topic)*100);
            }else{
                $incomplete_percent=0;
            $complete_percent=0;
            
            }
            
            $show_status=1;
            $lesson_result[]=array('name'=>$syllabus_reportvalue['name'],'topics'=>$topic_data,'incomplete_percent'=>$incomplete_percent,'complete_percent'=>$complete_percent);
          
        }
        
       $data['subjects_data'][$value->subject_group_subjects_id]['lesson_summary']=$lesson_result;
       
          


        } 


   $data['status']=array('1'=>$this->lang->line('complete'),'0'=>$this->lang->line('incomplete'));
        
        $this->load->view('layout/student/header', $data);
        $this->load->view('user/syllabus/status', $data);
        $this->load->view('layout/student/footer', $data);
    }

}
?>