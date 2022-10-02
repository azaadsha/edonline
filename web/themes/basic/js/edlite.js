(function (Drupal, $, once) {
    Drupal.behaviors.myBehavior = {
    
    
        attach: function (context, settings) {
    
          
            $('#school_other,#university_options,#polytechnic_options,#university_other,#polytechnic_other').hide();
          $('#edit-field-school').on('change',function() {
    
            var selected_school = $('#edit-field-school option:selected').text();
            if(selected_school == 'Other'){
                $('#school_other').show();
    
            }
            else{
                $('#school_other').hide();
            }
    
            
          });
    
          $('#edit-field-university').on('change',function() {
    
            var selected_university = $('#edit-field-university option:selected').text();
            if(selected_university == 'Others'){
                $('#university_other').show();
    
            }
            else{
                $('#university_other').hide();
            }
    
            
          });
    
          $('#edit-field-polytechnic').on('change',function() {
    
            var selected_polytechnic = $('#edit-field-polytechnic option:selected').text();
            if(selected_polytechnic == 'Others'){
                $('#polytechnic_other').show();
    
            }
            else{
                $('#polytechnic_other').hide();
            }
    
            
          });
    
          $('#edit-field-level').on('change',function() {
    
            var selected_level = $('#edit-field-level option:selected').text();
            if(selected_level == 'Poly/Diploma'){
                $('#polytechnic_options').show();
                $('#university_options,#school_options,#university_other,#polytechnic_other,#school_other').hide();
    
            }
            else if(selected_level == 'Engineering'){
    
              $('#university_options').show();
                $('#polytechnic_options,#school_options,#university_other,#polytechnic_other,#school_other').hide();
    
            }
            else{
              $('#school_options').show();
              $('#polytechnic_options,#university_options,#university_other,#polytechnic_other,#school_other').hide();
            }
    
            
          });
    
          
    
          $( ".timeslot" ).checkboxradio();
          $( ".datepicker" ).datepicker({
            minDate: new Date(),
           /* maxDate: "+1m +1w",*/
            showButtonPanel: true,
            dateFormat: 'dd/mm/yy'
          });
          $( "#views-exposed-form-session-list-page-1 #edit-field-session-date-value-1" ).datepicker({
            minDate: new Date(),
           /* maxDate: "+1m +1w",*/
            showButtonPanel: true,
            dateFormat: 'yy/mm/dd'
          });
    
         
    $('.element').on('click','a.delete-slot', function(ele){
        var total_element = $(".element").children().length;
        if(total_element > 1){
            var deleteId = $( this ).attr('id')
            var deleteIdArr = deleteId.split('-');
            $('#row-'+deleteIdArr[1]).remove();
            checkDateAndTime();
        }
        else{
            $('.slot-validation.messages.messages--error').html('You need to have atleast one slot');
            $('.slot-validation.messages.messages--error').show();
        }
    });
    
        $(".add").click(function(e){
    
          e.preventDefault();
          $('.slot-validation.messages.messages--error').hide();
          // Finding total number of elements added
          var total_element = $(".element").length;
                
          // last <div> with element class id
          var lastid = $(".element .row:last").attr("id");
        
          var split_id = lastid.split("-");
        
          var nextindex = Number(split_id[1]) + 1;
        
          var max = 8;
          // Check total number elements
          if(total_element < max ){
              // Adding new div container after last occurance of element class
             // $(".element .row:last").after("<div class='element' id='div_"+ nextindex +"'></div>");
              $(".element .row:last").after("<div class='row' style='border:1px solid #CCC;' id='row-"+ nextindex +"'></div>");
              var insideElements = "<a href='javascript:;' class='delete-slot' id='delete-"+nextindex+"'><i class='fa fa-times'></i> </a>";
              insideElements+= "<div class='col-md-4 thumb'><div class='desc'><h4>Select Date</h4><i class='fa fa-calendar' aria-hidden='true' id='slot_date-"+ nextindex +"'></i><input type='text' name='slot_date-"+ nextindex +"' class='form-control datepicker'></div></div>";
              insideElements+= "<div class='col-md-8 info calender-wrap'><fieldset id='field-set-"+ nextindex +"'>";
                var startTimeLabelsArray = ['09:00 AM','09:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM','12:30 PM','01:00 PM','01:30 PM','02:00 PM','02:30 PM','03:00 PM','03:30 PM','04:00 PM','04:30 PM','05:00 PM','05:30 PM','06:00 PM','06:30 PM']; 
                var startTimeValuesArray = ['09:00am','09:30am','10:00am','10:30am','11:00am','11:30am','12:00pm','12:30pm','01:00pm','01:30pm','02:00pm','02:30pm','03:00pm','03:30pm','04:00pm','04:30pm','05:00pm','05:30pm','06:00pm','06:30pm'];
              for(var i=1; i<=20; i++){
                insideElements+="<label for='radio-"+ nextindex +""+ i +"'>"+ startTimeLabelsArray[i-1]+"</label>";
                insideElements+="<input type='radio' name='radio-"+ nextindex + "' class='timeslot' id='radio-"+ nextindex +""+i + "' value='"+ startTimeValuesArray[i-1] +"'></input>"
              }
              // Adding element to <div>
              $("#row-" + nextindex).append(insideElements);
              $('#number_of_sessions').val((parseInt($('#number_of_sessions').val())) +1);
              $( ".timeslot" ).checkboxradio();
              $( ".datepicker" ).datepicker({
                minDate: new Date(),
                //maxDate: "+1m +1w",
                showButtonPanel: true,
                dateFormat: 'dd/mm/yy'
              });
                      
          }
    
    
                      
      });
    
      
     
      $('.slot-validation.messages.messages--error').hide();
     
      $('.element').on('change', 'input:radio', function(ele){
       
    
        
        if(checkDateAndTime()){
        
          console.log('iam in check 123');
          var radioName = ele.target.name;
          var radioNameArr = ele.target.name.split('-');
          var currentRow = radioNameArr[1];
          var currentDate = $('input[name=slot_date-'+currentRow+']').val();
          var currentTime =  $('#'+ele.target.id).val();
          console.log('going to validate slot');
          validate_slot_selection(currentDate,currentTime,currentRow);
        }
      });
      $('.element').on('change','.datepicker', function(ele){
        if(checkDateAndTime()){
          var elementName = ele.target.name;
          var elementNameArr = ele.target.name.split('-');
          var currentRow = elementNameArr[1];
          var currentDate = $('input[name=slot_date-'+currentRow+']').val();
          var currentTime =  $('input[name=radio-'+currentRow+']:checked').val();
          console.log(currentRow);
          console.log(currentTime);
          validate_slot_selection(currentDate,currentTime,currentRow);
        }
      });

      ////////////////////////
    
      function checkDateAndTime(){

        console.log('Check Date and Time Triggered');
        var flag = true;
             
        var numberOfSessions = $("#number_of_sessions").val();
        $('.slot-validation.messages.messages--error').hide();
    
        for(var i=1; i<=numberOfSessions; i++ ){

         console.log('The value of I is -->'+i);


          if($('#row-'+i).length){
            console.log($('#row-'+i).length);
            console.log('row lenght passed')
         // $('#row-'+i).removeClass('errorSlotRow');
          if(($('input[name=radio-'+i+']').is(':checked')==true)  && $('input[name=slot_date-'+i+']').val()!='' ){

            console.log('iam inside input I');
            console.log('slot -'+i+ 'date-->'+ $('input[name=slot_date-'+i+']').val());
            console.log('slot -'+i+ 'time-->'+ $('input[name=radio-'+i+']:checked').val());

            var slotDate1 = $('input[name=slot_date-'+i+']').val();
            var slotTime1 =  $('input[name=radio-'+i+']:checked').val();
           
          //Check if there is a conflict between any selected items
          
            if(numberOfSessions > 1){
            for(var j=i+1; j<=numberOfSessions; j++ ){
             
              console.log('The value of J='+j);
              if($('#row-'+j).length === 0 ){ console.log('skipping a row that is not found'); continue; }
                

                console.log('inside row length '+j);
              if(($('input[name=radio-'+j+']').is(':checked')==true)  && $('input[name=slot_date-'+j+']').val()!='' ){

                console.log('inside input of j loop');
              var slotdate2 = $('input[name=slot_date-'+j+']').val();
              var slotTime2 =  $('input[name=radio-'+j+']:checked').val();

              console.log('slot date -'+j+'-->'+slotdate2);
              console.log('slot time -'+j+'-->'+slotTime2);
              
              if(slotdate2 === slotDate1){
                  console.log('date is same');
                
                if(slotTime2 === slotTime1 ){

                  console.log('time is also  same');
    
                  $('.slot-validation.messages.messages--error').html('A session is already booked on the same date and time. Please modify.');
                  $('.slot-validation.messages.messages--error').css('display','block');
                  var offset = $(".slot-validation.messages.messages--error").offset();
                  $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                  $('#row-'+j).addClass('errorSlotRow');
                  flag =  false;
    
                }
                else{

                  console.log('time is not same');
    
                  var slotTimeOneArr = slotTime1.split(':');
                  var slotTimeTwoArr = slotTime2.split(':');
                  //check for am and pm
                  if((slotTime1.lastIndexOf("pm") !== -1) && (parseInt(slotTimeOneArr[0]) < 12) ){
                    console.log('iam in adding 12 hours');
                    slotTimeOneArr[0] = parseInt(slotTimeOneArr[0]) + 12 ;
                  }
                  if((slotTime2.lastIndexOf("pm") !== -1) && (parseInt(slotTimeTwoArr[0]) < 12) ){
                    slotTimeTwoArr[0] = parseInt(slotTimeTwoArr[0]) + 12 ;
                    console.log('iam in time slot 2  adding 12 hours');
                  }
                  //end check for am pm  
                  var slotTimeOneInSec = (parseInt(slotTimeOneArr[0])) * 60 * 60 + parseInt(slotTimeOneArr[1]) * 60 ;
                  var slotTimeTwoInSec = (parseInt(slotTimeTwoArr[0])) * 60 * 60 + parseInt(slotTimeTwoArr[1]) * 60 ;
                  
                 if(slotTimeOneInSec > slotTimeTwoInSec){
                  
                    var timeDiff = slotTimeOneInSec - slotTimeTwoInSec;
                  }
                  else{
                    
                  var timeDiff = slotTimeTwoInSec - slotTimeOneInSec;
                  }

                  
                  console.log('time diff --'+timeDiff);
                  if(timeDiff < 7200 ){
                    $('.slot-validation.messages.messages--error').html('The time difference between two sessions on same date should be at least 2 hours.Please modify');
                  $('.slot-validation.messages.messages--error').show();
                  var offset = $(".slot-validation.messages.messages--error").offset();
                  $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                  $('#row-'+j).addClass('errorSlotRow');
                    flag =  false;
                  }
    
                }
              }
              
            }
    
            
            
          } //End of J loop
          
          }
              
        }


        }
      }

      return flag;
       
        
    
      }
      //////////////////////


      
      function validate_slot_selection(currentDate,currentTime,currentRow){
    
        console.log('inside validate selection');
          $('.slot-validation.messages.messages--error').hide();
           
            if(!currentDate) return false;
            if(!currentTime) return false;
            if($('#row-'+currentRow).hasClass('errorSlotRow')){
              $('#row-'+currentRow).removeClass('errorSlotRow')
           }
           var flag;
           
            $.ajax({
              type: "POST",
              url: '/validate-session-slot',
              data: {
                'slotDate' : currentDate,
                'slotTime' : currentTime
              },
              async:false,
              
              dataType: 'json',
              success : function(result){
                if(result.message){
                  console.log('the result =>'+result.message);
                  $('#row-'+currentRow).addClass('errorSlotRow');
                  $('.slot-validation.messages.messages--error').html(result.message);
                  $('.slot-validation.messages.messages--error').show();
                  var offset = $(".slot-validation.messages.messages--error").offset();
                  $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                flag=false;
      
                }
                else{
                  flag = true;
                }
                
                
              }
            });
            return flag;
            //return true;
        }
        //End Of Slot booking validations
        
           //Slot booking validations
  
    $('#book_session').on('click',function(event){
  
        //var numberOfSessions = $(".element > .row").length;
        var numberOfSessions = $("#number_of_sessions").val();
       // var result_queue = [];
        for(var i=1; i<=numberOfSessions; i++ ){
  
          if($('#row-'+i).length){ //if 1 row deleted in between
            
            var slotDate1 = $('input[name=slot_date-'+i+']').val();
            var slotTime1 =  $('input[name=radio-'+i+']:checked').val();
            
            
                         
           //First check if the values are selected  
           
          if(($('input[name=radio-'+i+']').is(':checked')==false)  || $('input[name=slot_date-'+i+']').val()=='' ){
            $('.slot-validation.messages.messages--error').html('Please choose session date and time');
            $('.slot-validation.messages.messages--error').show();
            var offset = $(".slot-validation.messages.messages--error").offset();
           
            $('html, body').animate({
                scrollTop: offset.top-120,
                scrollLeft: offset.left
            }, 1000);
            $('#row-'+i).addClass('errorSlotRow');
            return false;
    
          }
          //Check if there is a conflict between any selected items
          else{
            if(validate_slot_selection(slotDate1,slotTime1,i)==false){
              return false;
         }
            if(numberOfSessions > 1){
            for(var j=i+1; j<=numberOfSessions; j++ ){
                if($('#row-'+j).length === 0 ){ continue; }
              var slotdate2 = $('input[name=slot_date-'+j+']').val();
              var slotTime2 =  $('input[name=radio-'+j+']:checked').val();
  
              if(($('input[name=radio-'+j+']').is(':checked')==false)  || $('input[name=slot_date-'+j+']').val()=='' ){
                $('.slot-validation.messages.messages--error').html('Please choose session date and time');
                $('.slot-validation.messages.messages--error').show();
                var offset = $(".slot-validation.messages.messages--error").offset();
               
                $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                $('#row-'+j).addClass('errorSlotRow');
                return false;
        
              }
    
              if(slotdate2 === slotDate1){
    
    
                if(slotTime2 === slotTime1 ){
    
                  $('.slot-validation.messages.messages--error').html('A session is already booked on the same date and time. Please modify.');
                  $('.slot-validation.messages.messages--error').show();
                  var offset = $(".slot-validation.messages.messages--error").offset();
                  $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                  $('#row-'+j).addClass('errorSlotRow');
                  return false;
    
                }
    
                else{
    
                  var slotTimeOneArr = slotTime1.split(':');
                  var slotTimeTwoArr = slotTime2.split(':'); 
                   //check for am and pm
                    if((slotTime1.lastIndexOf("pm") !== -1) && (parseInt(slotTimeOneArr[0]) < 12) ){
                      slotTimeOneArr[0] = parseInt(slotTimeOneArr[0]) + 12 ;
                    }
                    if((slotTime2.lastIndexOf("pm") !== -1) && (parseInt(slotTimeTwoArr[0]) < 12) ){
                      slotTimeTwoArr[0] = parseInt(slotTimeTwoArr[0]) + 12 ;
                    }
                    //end check for am pm  	
                  var slotTimeOneInSec = (parseInt(slotTimeOneArr[0])) * 60 * 60 + parseInt(slotTimeOneArr[1]) * 60 ;
                  var slotTimeTwoInSec = (parseInt(slotTimeTwoArr[0])) * 60 * 60 + parseInt(slotTimeTwoArr[1]) * 60 ;
                 if(slotTimeOneInSec > slotTimeTwoInSec){
                    var timeDiff = slotTimeOneInSec - slotTimeTwoInSec;
                  }
                  else{
                  var timeDiff = slotTimeTwoInSec - slotTimeOneInSec;
                  }
                  
                  if(timeDiff < 7200 ){
                    $('.slot-validation.messages.messages--error').html('The time difference between two sessions on same date should be at least 2 hours.Please modify');
                  $('.slot-validation.messages.messages--error').show();
                  var offset = $(".slot-validation.messages.messages--error").offset();
                  $('html, body').animate({
                    scrollTop: offset.top-120,
                    scrollLeft: offset.left
                }, 1000);
                  $('#row-'+j).addClass('errorSlotRow');
                    return false;
                  }
    
                }
              } 
    
            
          }
            
          }
          }//End else
          return true;
    
        }
      }
  
       
       
        
    
      }); 
        
    
    
      //Subject selection validation
      
     $('#subject-pass').on('click',function(){
    
      
      if(($('input[name=selected-subject]').is(':checked')==false)){
    
        $('.subject-validation.messages.messages--error').html('Please select the subject to proceed.');
        $('.subject-validation.messages.messages--error').show();
        return false;
    
      }
      else if($('input[name=selected-subject]:checked').val() == 10 || $('input[name=selected-subject]:checked').val() == 11 ){
    
       if($('input[name=selected-subject]:checked').val() == 10){
          if($('#language-text').val()==''){
            $('#language-text').addClass('error');
            return false;
          }
       }
       if($('input[name=selected-subject]:checked').val() == 11){
        if($('#others-text').val()==''){
          $('#others-text').addClass('error');
          return false;
        }
      }
    
      }
      });
    
      $('input[name=selected-subject]').on('change', function(){
        $('.subject-validation.messages.messages--error').hide();
      });
      
      
      $('.sublevel-display .nav-pills li').on('click',function(){
        var selected_sublevel_id = $(this).attr('id');
        $('input#sub-level').val(selected_sublevel_id);
      });
      
    
      
      $('.path-slot-booking').on('click','.fa.fa-calendar', function(ele){
       
        var currentElement = ele.currentTarget.id;
        
        $('.path-slot-booking input[name='+currentElement+']').trigger('focus');
        
      });
    
      $('#subjects-row input[name=selected-subject]').on('change', function(ele){
        
        var selectedValue = $( this ).val();
        if(selectedValue==10){
          $('#language-div').show();
          $('#subject-div').hide();
          $('#others-text').removeClass('error');
        }
        else if(selectedValue==11){
    
          $('#language-div').hide();
          $('#subject-div').show();
          $('#language-text').removeClass('error');
    
        }
        else{
    
          $('#language-div').hide();
          $('#subject-div').hide();
          $('#language-text').removeClass('error');
          $('#others-text').removeClass('error');
        }
    
      });
    
      $('#others-text,#language-text').focus(function(){
    
        if($('#others-text,#language-text').hasClass('error')){
          $('#others-text,#language-text').removeClass('error');
       }
      });
    
    
    
      $('#module-pass').on('click',function(){
        if(($('#selected-subject').val()==0)){
      
          $('.subject-validation.messages.messages--error').html('Please select the module to proceed.');
          $('.subject-validation.messages.messages--error').show();
          return false;
      
        }
        });
      
        $('#selected-subject').on('change', function(){
          $('.subject-validation.messages.messages--error').hide();
        });
    
      
      //End Subject selection validation
    
      //setting Session time on Session Edit page
      $('#edit-field-session-time').on('change',function(){
    
       
        $('input[name=session_start_time]').val($( "#edit-field-session-time option:selected" ).text());
        $('input[name=session_start_date]').val($( "#edit-field-session-date-0-value-date" ).val());
    
        });
        $('#edit-field-session-end-time').on('change',function(){
    
          $('input[name=session_end_time]').val($( "#edit-field-session-end-time option:selected" ).text());
      
          });  
    
          $('#edit-field-session-date-0-value-date').on('change',function(){
    
            $('input[name=session_start_date]').val($( "#edit-field-session-date-0-value-date" ).val());
            $('input[name=session_start_time]').val($( "#edit-field-session-time option:selected" ).text());
    
        
            });   
            
            $('#level_selection').on('change',function(){
    
              var selectedOption = $( "#level_selection option:selected" ).val();
              console.log(selectedOption);
    
              $('#level_submit_form').attr('action', '/level/'+selectedOption);
      
          
            });   
    
    
           
    
    
    
          
        }
      };
    
    }(Drupal, jQuery, once));
      