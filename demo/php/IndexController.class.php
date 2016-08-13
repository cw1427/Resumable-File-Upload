<?php
namespace Home\Controller;
use Org\Imgupload\UploadFile;
use Common\Generic\GenericController;
use Util\ConstUtil;
use Think\Controller;
use Home\Model\ResumeUploadModel;

class IndexController extends GenericController {
    public function indexAction(){
		if(is_login()){
			//---TODO: need to prepare the menu and the user authon and 
			//the anounce alarm infos to render.
			$this->prepareData();
			session('ticketPName',null);//----移除session中上载的ticket截图 
        	$this->display();
         }else{
            $this->redirect('Login/Login/index');
        }	
    }
    
    public function prepareData(){
    	$ticketModel=new \Service\Model\TicketModel();
    	$filter['ticketState']=array('lt',ConstUtil::TICKET_CLOSE);
    	$filter['memberCode']=$this->currentUser['memberCode'];	
    	$tCount=$ticketModel->where($filter)->count(1);
    	$this->assign('tCount',$tCount);
    	$ticketlist=$ticketModel->where($filter)->order('id desc')->limit(5)->select();
    	$this->assign('ticketlist',$ticketlist);
    	//----prepare the area
	areaList=M()->query("select areaCode,areaNamePort from meta_area where areaType=1 and areaFlag=1");
	$this->assign("areaList",$areaList);
    }
    
    public function changePWDAction(){
    	\Think\Log::record(__ACTION__, \Think\Log::DEBUG);
    	$oldPWD=I('oldPWD');
    	$newPWD=I('newPWD');
    	$std=new \stdClass();
	    $std->error='';
    	$memberModel=new \Login\Model\MemberModel();
    	$currentMember=$memberModel->where(array('memberCode'=>$this->currentUser['memberCode'],'memberPassword'=>think_auth_md5($oldPWD, UC_AUTH_KEY)))->find();
    	if(!empty($currentMember)){
    		$memberModel->where(array('memberCode'=>$this->currentUser['memberCode']))->save(array('memberPassword'=>think_auth_md5($newPWD, UC_AUTH_KEY))); 
    		$std->msg="success";
    		fnRecordAction('changeLogPWD', $this->currentUser['memeberCode'].' change password', ConstUtil::ACTION_BUS);
    	}else{
    		$std->error='failed';
    	}
    	
    	$this->ajaxReturn($std);
    	
    }

    public function ticketimgUploadAction(){
    	\Think\Log::record(__ACTION__, \Think\Log::DEBUG);
    	$res=new \stdClass();
    	if (isset($_FILES['file1'])){
    		import("Org.Imgupload.UploadFile");
    		$upload = new UploadFile();  
    		 $upload->maxSize = 5292200;
	        $upload->allowExts = explode(',', 'jpg,gif,png,jpeg'); 
	        $upload->savePath = C('BUSUPLOAD').'/TICKET/TEMP/'; 代
	        $upload->thumb = false;
	        $upload->saveRule = 'uniqid'; 
	        $upload->thumbRemoveOrigin = false; 
	        if (!$upload->upload()) { 
	            $this->error($upload->getErrorMsg()); 
	        } else { 
            	$uploadList = $upload->getUploadFileInfo(); 
            	session('ticketPName',$uploadList[0]['savename']);
            	\Think\Log::record("Saving file:". $uploadList[0]['savename'], \Think\Log::DEBUG);
            	$res->error='';
				$res->fileName=$uploadList[0]['savename'];
				//$this->ajaxReturn($res);
				$this->ajaxReturn($res,'AJAXFILEUPLOAD');	
	        }	
    	}else{
    		session('ticketPName',null);
    		\Think\Log::record("no upload", \Think\Log::DEBUG);
    	}
    	
    }
    
public function addTicketAction(){
    	\Think\Log::record(__ACTION__, \Think\Log::DEBUG);
    	$ticketModel=new \Service\Model\TicketModel();
    	$ticketModel->ticketTitle=I('post.ticketTitle');
    	$ticketModel->ticketType=I('post.typeSelector');
    	$ticketModel->ticketDate=date('Y-m-d H:i:s',time());
    	$ticketModel->ticketArea=I('post.areaSelector');
    	$ticketModel->ticketTN=I('post.tn');
    	$ticketModel->ticketLN=I('post.ln');
    	$ticketModel->ticketSN=I('post.sn');
    	$ticketModel->ticketDetail=I('post.ticketDetail');
    	$ticketModel->userCode=I('post.userCode');
    	$ticketModel->ticketCode=fnGenerateTicketCode();
    	$ticketModel->ticketState=ConstUtil::TICKET_OPEN;
    	$ticketModel->memberCode=$this->currentUser['memberCode'];
    	$ticketModel->ticketInnerType=ConstUtil::TICKET_IN_CUSTOMA;
    	$ticketImg=session('ticketPName');
    	if (!empty($ticketImg)){
    		$ticketModel->ticketImg=session('ticketPName');
    		copy(C('BUSUPLOAD').'/TICKET/TEMP/'.$ticketModel->ticketImg, C('BUSUPLOAD').'/TICKET/'.$ticketModel->ticketImg);
    		unlink(C('BUSUPLOAD').'/TICKET/TEMP/'.$ticketModel->ticketImg);
    	}
    	$ticketModel->ticketCreater=$this->currentUser['memberCode'];
    	$ticketModel->add();	
    	session('ticketPName',null);
    	$this->success('添加ticket成功',"/Home/Index/index");
  	 
    }
 public function CRMSearchAction(){
    	\Think\Log::record(__ACTION__,\Think\Log::DEBUG);
    	$kw=I('searchCode');
    	$type=I('type');
    	if(empty($type)){$type=1;}
    	
    	switch ($type){
    			case 1:
    				$filter['userCode']=$kw;
    				break;
    			case 2: 
    				$filter['userPin']=$kw;
    				break;
    			case 3: 
    				$filter['userPinCode']=$kw;
    				break;
    			case 4: 
    				$filter['userTel']=$kw;
    				break;							
    			case 5: 
    				$filter['userEmail']=$kw;
    				break; 
    		} 
    	$userModel=new \Login\Model\ShipuserModel();
    	$us=$userModel->where($filter)->find();	
    	$std=new \stdClass();
    	if(empty($us))
    	{
    		$std->error='没找到相关客户';
    		$std->msg='';
    		
    	}
    	else
    	{   $std->error='';
    		$std->msg=$us['userCode'];
    	} 
	    $this->ajaxReturn($std);
    	
    }
    
	public function ORSearchAction(){
    	\Think\Log::record(__ACTION__,\Think\Log::DEBUG);
    	$kw=I('searchCode');
    	if(empty($kw))
    	{
    		$std->error='1';
    		$std->msg='没找到相关订单记录';
    	}
    	else
    	{
    		$orderModel=new \Order\Model\OrderModel();
	    	$subFilter['upsCode']=array('like','%'.$kw.'%');  				
	    	$subFilter['listCode']=array('like','%'.$kw.'%');
	    	$subFilter['listAAECode']=array('like','%'.$kw.'%');  
	    	$subFilter['listAAECodeOld']=array('like','%'.$kw.'%'); 
	    	$subFilter['_logic']='OR';	
	    	$filter['_complex']=$subFilter;
	    	$om=$orderModel->where($filter)->select();
	    	$num=count($om);
	    	if(0==$num)
	    	{
	    		$std->error='1';
    			$std->msg='没找到相关订单记录';
	    	}
	    	elseif (1==$num)
	    	{
	    		$std->error='2';
    			$std->msg=$om[0]['listCode'];
	    	}
	    	else
	    	{
	    		$std->error='3'; 
	    		foreach ($om as $k=>$v)
	    		{
	    			$result[$k]=$v['listCode'];
	    		}
    			$std->msg=$result;
	    	}
    	} 
	    $this->ajaxReturn($std);
    	
    }
    
public function PGSearchAction(){
    	\Think\Log::record(__ACTION__,\Think\Log::DEBUG);
    	$kw=I('searchCode');
    	if(empty($kw))
    	{
    		$std->error='1';
    		$std->msg='没找到相关订单记录';
    	}
    	else
    	{
    		$pgModel=new \Store\Model\PortgoodsModel();  
	    	// 区域统计控制 
		    if($this->currentUser['memberArea']=='DEL'){
				$filter['pgArea']='DEL';
			}elseif ($this->currentUser['memberArea']=='LAX'){
				$filter['pgArea']='LAX';
			}  
			$filter['pgCode']=array('like','%'.$kw.'%');
			 
	    	$om=$pgModel->where($filter)->select();   
	    	$num=count($om);
	    	if(0==$num)
	    	{
	    		$std->error='1';
    			$std->msg='没找到相关订单记录';
	    	}
	    	elseif (1==$num)
	    	{
	    		$std->error='2';
    			$std->msg=$om[0]['pgCode'];
	    	}
	    	else
	    	{
	    		$std->error='3'; 
	    		foreach ($om as $k=>$v)
	    		{
	    			$result[$k]=$v['pgCode'];
	    		}
    			$std->msg=$result;
	    	}
    	} 
	    $this->ajaxReturn($std);
    	
    }
    
    public function checkUserExistAction(){
    	$uerCode=I('uc');
    	$count=M()->query("select count(1) from ship_user where userCode='".$uerCode."'");
    	$res=new \stdClass();
    	$res->error='';
    	if ($count[0][null]>0){
    		$res->msg='yes';
    	}else{
    		$res->msg='no';
    	}
    	$this->ajaxReturn($res);
    	
    }
    
    public function readMessageAction(){
    	$id=I('id');
    	
    	M()->execute("update ship_message set messageState=1 where id=".$id);
    	$res=new \stdClass();
    	$res->error='';
    	$res->msg='success';
    	$this->ajaxReturn($res);
    }
    
    //-----test for the resumable.js
    public function resumeAction(){
    	 \Think\Log::record(__ACTION__ . '----resumeAction',\Think\Log::DEBUG);
    	 $this->display();
    }
    
    public function nullAction(){
    	 \Think\Log::record(__ACTION__ . '----resumeAction',\Think\Log::DEBUG);
    	 $this->display();
    }
    
    public function resumeUploadAction(){
    	\Think\Log::record(__ACTION__ . '----resumeuploadAction = ' . REQUEST_METHOD ,\Think\Log::DEBUG);
    	$resumableChunkNumber = I('resumableChunkNumber');
    	$resumableChunkSize = I('resumableChunkSize');
    	$resumableIdentifier = I('resumableIdentifier');
    	$totalChunks = I('resumableTotalChunks');
    	\Think\Log::record('---chunkNumber=' . $resumableChunkNumber . ' ---chunkSize=' . $resumableChunkSize . ' ---name=' .$name, \Think\Log::DEBUG);
    	
    	//header('HTTP/1.1 500 Error');
    	//----从session 中查看 当前的chunkNumber
    	$resumeModel=new \Home\Model\ResumeUploadModel();
    	if (IS_GET) {
	    	$resume=$resumeModel->where(array('guid'=>$resumableIdentifier))->find();
	    	if (!empty($resume)){
	    		if ($resume['currentChunk']>=$resumableChunkNumber){
	    			header('HTTP/1.1 200 Ok');
	    			//$this->ajaxReturn(array('status'=>'success'),200);
	    			return;
	    		}
	    	}
	    	header('HTTP/1.1 404 Not found');
	    	//$this->ajaxReturn(array('status'=>'success'),404);
    	}elseif (IS_POST) {
    		 \Think\Log::record( 'post upload chunk data test name=' . $name ,\Think\Log::DEBUG);
    		//----TODO write the data into temp file
    		//----create temp folder
    		$resumableFilename=I('resumableFilename');
    		\Think\Log::record('---resumableFilename=' . $resumableFilename . '--filetype=' . $_FILES['file']['type'], \Think\Log::DEBUG);
    		$dest_file = C('UPLOAD_DIR') . $resumableIdentifier . '.temp';
    		if (!file_exists($dest_file)){
    			move_uploaded_file($_FILES['file']['tmp_name'], $dest_file);
    		}else{
    			//----append
    			\Think\Log::record( '----append to file' ,\Think\Log::DEBUG);
		         file_put_contents($dest_file,file_get_contents($_FILES['file']['tmp_name']),FILE_APPEND);
		        //----if completed change the name
		        if ($resumableChunkNumber >=$totalChunks){
		        	rename($dest_file,dirname($dest_file).'/'.$resumableFilename);
		        }
    		}
    		$resume=$resumeModel->where(array('guid'=>$resumableIdentifier))->find();
    		if (empty($resume)){
    			$resumeModel->add(array('guid'=>$resumableIdentifier,'currentChunk'=>$resumableChunkNumber,'totalChunk'=>$totalChunks));
    		}else {
    			$data['currentChunk'] = $resumableChunkNumber;
    			$resumeModel->where(array('guid'=>$resumableIdentifier))->save($data);
    		}
    		header('HTTP/1.1 200 Ok');
	    	//$this->ajaxReturn(array('status'=>'success'),200);
    	}	
    }
    
    public function checkFileChunkStartOffsetAction(){
    	$identify = I('identify');
    	\Think\Log::record( '----check chunk offset=' . $identify ,\Think\Log::DEBUG);
    	if (empty($identify)){
    		header('HTTP/1.1 400 Ok');
    		$this->ajaxReturn(array('error'=>'parms error'));
    	}
    	$resumeModel=new \Home\Model\ResumeUploadModel();
    	$resume=$resumeModel->where(array('guid'=>$identify))->find();
    	$offset = 0;
    	if (!empty($resume)){
    		$offset = $resume['currentChunk'];
    	}
    	header('HTTP/1.1 200 Ok');
    	$this->ajaxReturn(array('offset'=>$offset),'AJAXFILEUPLOAD');
    }
    
    public function normalUploadAction(){
    	\Think\Log::record( '----call normalUpload' ,\Think\Log::DEBUG);
    	///-----upload 
    	$config = array(
			 'maxSize' => 52428800, ///---50M
			 'rootPath'=>'./',
			 'savePath' => './Upload/temp/',
			 'saveName' => array('time'), //array('date','Y-m-d'),
			 'exts' => array('xls','csv','xlsx','zip'),
			 'autoSub' => false,
			);
		$upload = new \Think\Upload($config);// 实例化上传类
		$info = $upload->upload();
		if(!$info) {// 上传错误提示错误信息
				\Think\Log::record("portgoods upload error:".$upload->getError(), \Think\Log::ERR);
				$this->error($upload->getError(),U('Home/Index/resume'));
		}else{// 上传成功 获取上传文件信息
			\Think\Log::record("successed uploaded", \Think\Log::DEBUG);
		}
		header('HTTP/1.1 200 Ok');
    	$this->ajaxReturn(array('status'=>'success','files'=>array(array('name'=>$info[0]['name']))),'AJAXFILEUPLOAD');
    	
    }
}