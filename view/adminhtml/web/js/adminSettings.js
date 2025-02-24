require([
    "jquery",
    "mage/mage"
],function($) {
    $(document).ready(function() {
        $('#zept-oauth-form').mage(
            'validation',
            { 
                submitHandler: function(form) {
					event.preventDefault();
					$self = $('[name=submit]');
                    $.ajax({
                        url: $(this).attr("action"),
                        data: $('#zept-oauth-form').serialize(),
                        type: 'POST',
                        dataType: 'json',
                        beforeSend: function() {
							if($self.find(".loading-spinner").length === 0){
								$self.append($('<div>').addClass("loading-spinner"));
							}else {
								xhr.abort();
								return;
							}
							$('label.zepto_error').remove();
                            $('.zepto_error').removeClass('zepto_error');
                        },
                        success: function(data, status, xhr) {
							$self.find(".loading-spinner").remove();
							if(data.result === 'success'){
								addZeptoSuccessMessage('Plugin configured successfully');
								window.scrollTo(0,0);
							}
							else {
								if(data.hasOwnProperty('error_message')){
									addZeptoErrorMessage(data['error_message']);
									window.scrollTo(0,0);
								}
								else if(data.hasOwnProperty('email_error')){
									$email_error = data.email_error;
									$.each($email_error,function(index,item){
										
										$label = $('<label>').attr("id",item['type']+"-error").addClass("zepto_error").attr("for",item['type']).html(item['error']['message']);
										$('#'+item['type']).addClass('zepto_error');
										$label.insertAfter($('[name='+item['type']+']'));
										
									});
									
								}
							}
                            
                        }
                    });
                }
            }
        );
		
		$("#zepto_test_btn").on('click', function(){
			$self = $(this);
			$.ajax({
				url: $(this).attr("action"),
				data: {
					'option' : 'testOauthSettings',
					'form_key' : $('[name=form_key]').val()
				},
				type: 'POST',
				dataType: 'json',
				beforeSend: function() {
					if($self.find(".loading-spinner").length === 0){
						$self.append($('<div>').addClass("loading-spinner"));
					}else {
						xhr.abort();
						return;
					}
					$('label.zepto_error').remove();
					$('.zepto_error').removeClass('zepto_error');
				},
				success: function(data, status, xhr) {
					$self.find(".loading-spinner").remove();
					if(data.result == 'success'){
						addZeptoSuccessMessage('Configuration working fine');
					}
					else {
						if(data.hasOwnProperty('error_message')){
							addZeptoErrorMessage(data['error_message']);
						}
						else if(data.hasOwnProperty('email_error')){
							$email_error = data.email_error;
							$.each($email_error,function(index,item){
										$label = $('<label>').attr("id",item['type']+"-error").addClass("zepto_error").attr("for",item['type']).html(item['error']['message']);
										$('#'+item['type']).addClass('zepto_error');
										$label.insertAfter($('[name='+item['type']+']'));
							});
								
						}
					}
                            
                }
			});
		});
		
    });
	
});
