function Show_Child(num){
	if (document.getElementById('branch_'+num).style.display=='none'){
		document.getElementById('branch_'+num).style.display='block';
		
		if (document.getElementById('parent_'+num).className=='parent'){
			document.getElementById('parent_'+num).className='parent_s';
		}else if (document.getElementById('parent_'+num).className=='parent_first'){
			document.getElementById('parent_'+num).className='parent_first_s';
		}else if (document.getElementById('parent_'+num).className=='parent_last'){
			document.getElementById('parent_'+num).className='parent_last_s';
		}
		
	}else{
		document.getElementById('branch_'+num).style.display='none';
		
		if (document.getElementById('parent_'+num).className=='parent_s'){
			document.getElementById('parent_'+num).className='parent';
		}else if (document.getElementById('parent_'+num).className=='parent_first_s'){
			document.getElementById('parent_'+num).className='parent_first';
		}else if (document.getElementById('parent_'+num).className=='parent_last_s'){
			document.getElementById('parent_'+num).className='parent_last';
		}
		
	}
}