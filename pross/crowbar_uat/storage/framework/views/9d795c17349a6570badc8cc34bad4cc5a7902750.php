<?php $__env->startSection('requirecss'); ?>
<?php $__env->stopSection(); ?>



<?php $__env->startSection('inlinecss'); ?>

<?php $__env->stopSection(); ?>



<?php $__env->startSection('requirejs'); ?>
	<script type="text/javascript">
		function addReply(id_reply){
		$('#text-reply-area-'+id_reply).toggle();
		$("#reply-area-"+id_reply).hide();
			// $(".ask-question").toggle();
		}
		function insertReply(id_reply){
			var add_reply_url = $('#add-reply').val();
			var answer_description = $('#answer_description_'+id_reply).val();
			var answer_type = $('select[name=answer_type_' + id_reply + '] option:selected').val();
			
			if(answer_description.length <= 0){
				$('#text-reply-area2-'+id_reply).addClass('has-error');
				$('#text-reply-error-area-'+id_reply).html('<div class="help-block">Enter your answer.</div>');
			}else{
				$('#text-reply-area2-'+id_reply).removeClass('has-error');
				$('#text-reply-error-area-'+id_reply).html('');
			}

			if(id_reply > 0 && answer_description.length > 0){
				$.ajax({
					method: "PUT",
					url: add_reply_url,
					data: { id_parent: id_reply, answer_description: answer_description, type: answer_type}
				}).done(function(data) {
					$('#text-reply-area-'+id_reply).toggle();
					$('#answer_description_'+id_reply).val('');
					$('#add-reply-response-'+id_reply).html(data.message);
					$('#add-reply-response-'+id_reply).fadeIn('slow');
					$('#add-reply-response-'+id_reply).fadeOut(9000);
					setTimeout(function(){
						location.reload();
					},2000);
				});
			}
		}
		function loadReply(id_reply){
			var reply_list_url = $('#list-reply').val();
			if(id_reply > 0){
				$.ajax({
					method: "POST",
					url: reply_list_url,
					data: { id_reply: id_reply}
				})
				.done(function(data) {
					$("#reply-area-"+id_reply).html(data);
					$("#reply-area-"+id_reply).show();
					$('#text-reply-area-'+id_reply).hide();
				});
			}
		}
		function closeReplyArea(id_reply){
			$('#text-reply-area-'+id_reply).hide();
		}

		$(document).on('click','[data-request="up-vote-answer"]',function(){
			$('#popup').show(); 
			var $this   = $(this);
			var $url    = $this.data('url');

			$.ajax({
				url: $url, 
				cache: false, 
				contentType: false, 
				processData: false, 
				type: 'get',
				success: function($response){
					$('#popup').hide();
					if($this.hasClass('active')){
						$this.removeClass('active');
					}else{
						$this.addClass('active');
						var down_vote_class = $this.next().hasClass('active');
						if(down_vote_class){
							$this.next().removeClass('active');
						}
					}

					$('#upvote_count_'+$response.answer_id).html($response.data.upvote_count);
					$('#downvote_count_'+$response.answer_id).html($response.data.downvote_count);

				},error: function(error){
					$('#popup').hide();
				}
			}); 
		});

		$(document).on('click','[data-request="down-vote-answer"]',function(){
			$('#popup').show(); 
			var $this   = $(this);
			var $url    = $this.data('url');

			$.ajax({
				url: $url, 
				cache: false, 
				contentType: false, 
				processData: false, 
				type: 'get',
				success: function($response){
					$('#popup').hide();
					if($this.hasClass('active')){
						$this.removeClass('active');
					}else{
						$this.addClass('active');
						var up_vote_class = $this.prev().hasClass('active');
						if(up_vote_class){
							$this.prev().removeClass('active');
						}
					}

					$('#upvote_count_'+$response.answer_id).html($response.data.upvote_count);
					$('#downvote_count_'+$response.answer_id).html($response.data.downvote_count);

				},error: function(error){
					$('#popup').hide();
				}
			}); 
		});

		$('#give_answer').on('click',function(){
			if($('#ask_main_answer').hasClass('none')){
				$('#ask_main_answer').show();
				$('#ask_main_answer').removeClass('none');
				$('#ask_main_answer').addClass('show');
			}else{
				$('#ask_main_answer').hide();
				$('#ask_main_answer').removeClass('show');
				$('#ask_main_answer').addClass('none');
			}
		});

        $(document).on('click','[data-request="follow-post"]',function(){
            $('#popup').show(); 
            var $this = $(this);
            var $url    = $this.data('url');
            $.ajax({
                url: $url, 
                cache: false, 
                contentType: false, 
                processData: false, 
                type: 'get',
                success: function($response){
                    $('#popup').hide();
                    if( $this.hasClass('active')){
                        $this.removeClass('active');
                        $this.html($response.data);
                    }else {
                        $this.addClass('active');
                        $this.html($response.data);
                    }
                },error: function(error){
                    $('#popup').hide();
                }
            });
        });

	</script>
<?php $__env->stopSection(); ?>

	<?php $__env->startSection('content'); ?>
	<!-- Banner Section -->
	<?php if(Request::get('stream') != 'mobile'): ?>
	<div class="static-heading-sec">
		<div class="container-fluid">
			<div class="static Heading">                    
				<h1>Question Details</h1>                        
			</div>                    
		</div>
	</div>
	<?php endif; ?>
	<!-- /Banner Section -->
	<!-- Main Content -->
	<div class="contentWrapper">
		<section class="aboutSection questions-listing">
			<div class="container">
				<div class="row">
					<div class="col-md-8 col-sm-8 col-xs-12">
						<div class="left-question-section question-details">
							<div class="details">
								<ul class="general-questions-list">
									<li>
										<div class="question-wrap question-wrap-detail">
											<h5><?php echo e($question['question_description']); ?></h5>
											<div class="question-author question-detail-author question-listing">
												<div class="posted-on">
													<div class="question-author-action">
														<label class="posted-label">Posted <span class="posted-date"> <?php echo e(___ago($question['approve_date'])); ?></span></label>
													</div>
												</div>
											</div>
											<div class="row shared-row">
												<div class="col-md-3 col-sm-3 col-xs-12">
													<div class="count-wrap">
														<h6 class="reply-counts"><?php echo e($question['total_reply']); ?> <?php echo e(str_plural('Reply',$question['total_reply'])); ?></h6>
													</div>
												</div>
												<div class="col-md-9 col-sm-9 col-xs-12">
													<div class="listing-dropdown text-right">
														<ul>
															<li>
																<?php if(!empty(\Auth::user()) && \Auth::user()->id_user != $question['id_user']): ?>
																<?php 
																if($question['is_ques_following'] == 1){
																	$is_question_following = 'active';
																	$follow_text_ques = 'Following this Question';
																}else{
																	$is_question_following = '';
																	$follow_text_ques = 'Follow this Question';
																}
																 ?>
																<a href="javascript:void(0);" class="follow-icon <?php echo e($is_question_following); ?>" data-request="follow-post" data-url="<?php echo e(url(sprintf('/mynetworks/community/follow-post?post_id=%s&section=%s',$question['id_question'],'question'))); ?>"><?php echo e($follow_text_ques); ?>

																</a>
																<?php endif; ?>
															</li>
															<li>
																<div class="dropdown socialShareDropdown">
																	<a href="javascript:void(0);" data-toggle="dropdown" aria-expanded="false"><?php echo e(trans("website.W0908")); ?></a>
																	<ul class="dropdown-menu">
																		<?php 
																		$question_share_url = url("/network/community/forum/question/".___decrypt($id_question));
																		 ?>
																		<li>
																			<a href="javascript:void(0);" class="linkdin_icon">
																				<script src="//platform.linkedin.com/in.js" type="text/javascript"> lang: en_US</script>
																				<script type="IN/Share" data-url="<?php echo e($question_share_url); ?>"></script>
																				<img src="<?php echo e(asset('images/linkedin.png')); ?>">
																			</a>
																		</li>
																		<li>
																			<a class="fb_icon" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo e($question_share_url); ?>" target="_blank">
																				<img src="<?php echo e(asset('images/facebook.png')); ?>">
																			</a>
																		</li>
																		<li>
																			<a href="https://web.whatsapp.com/send?text=<?php echo e($question_share_url); ?>" target="_blank" id="whatsapp_link" data-action="share/whatsapp/share"><img src="<?php echo e(asset('images/whatsapp-logo.png')); ?>"></a>
																		</li>
																	</ul>
																</div>   
															</li>
															<li>
																<div class="post-answer">
																	<a id="give_answer" class="reply-answer">Post answer</a>
																</div>
															</li>
														</ul>
													</div> 
												</div>
											</div>    
										</div>
									</li>                               
								</ul>
								<?php if(!empty(\Auth::user())): ?>
								<div class="ask-question none" id="ask_main_answer" style="display:none;">
									<form role="add-talent" action="<?php echo e(url('/network/community/forum/answer/add/'.$id_question)); ?>" method="POST" class="question-form">
										<input type="hidden" name="_method" value="PUT">
										<?php echo e(csrf_field()); ?>

										<input type="hidden" name="id_parent" value="0">
										<div class="questionform-box">
											<p>Post Your Answer</p>
											<div class="form-element form-group big-textarea">
												<textarea name="answer_description" class="form-control" placeholder="Enter Your Answer"></textarea>
											</div>
											<?php if($company_profile != 'individual'): ?>
											<div class="form-group form-element">
												<div>
													<select name="type">
														<option value="individual" selected="selected">Post as <?php echo e(\Auth::user()->name); ?></option>
														<option value="firm">Post as firm</option>
													</select>                                               
												</div>
											</div>
											<?php else: ?>
											<div class="form-group form-element" style="display:none;">
												<div>
													<select name="type">
														<option value="individual" selected="selected">Post as <?php echo e(\Auth::user()->name); ?></option>
													</select>                                               
												</div>
											</div>
											<?php endif; ?>
											<div class="form-group button-group">
												<div class="form-btn-set submit-solution">
													<input data-request="ajax-submit" data-target='[role="add-talent"]' type="button" class="button" value="<?php echo e(trans('website.W0393')); ?>" />
												</div>
											</div>                                
										</div>
									</form>
								</div>
								<?php endif; ?>
							</div>
							<div class="answers-list answer-list-wrapper">
								<div class="question-detail-head">
									<div class="row">
										<div class="col-md-6 col-sm-6 col-xs-12">
											<h6>All Answers (<?php echo e(count($answer)); ?>)</h6>
										</div>
										<div class="col-md-6 col-sm-6 col-xs-12" style="float:left;">
											<div class="form-group form-element">
												<div class="text-right sort-dropdown">
													<select id="order" name="order" onchange="window.location='?order='+this.value;">
														<option>Sort by</option>
														<option value="ASC" <?php if($orderBy == 'ASC'): ?> selected="selected" <?php endif; ?>>Posted Date (ASC)</option>
														<option value="DESC" <?php if($orderBy == 'DESC'): ?> selected="selected" <?php endif; ?>>Posted Date (DESC)</option>
														<option value="Upvote" <?php if($orderBy == 'Upvote'): ?> selected="selected" <?php endif; ?>>Most Upvoted</option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
								<ul class="answer-chat">
									<?php $__currentLoopData = $answer; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getFirstLoop(); ?>
									<li class="promoted-answer">
										<div class="answer-wrapper">
											<div class="answer-level">
												<p> <?php echo e($a['answer_description']); ?></p>
												<div class="question-author listing-author-wrapper question-listing">
													<div class="flex-cell answer-cell">
														<?php if(!empty($a['filename'])): ?>
														<img src="<?php echo e(asset($a['filename'])); ?>" alt="image" class="question-author-image">
														<?php else: ?>
														<img src="<?php echo e(asset('images/sdf.png')); ?>" alt="image" class="question-author-image">
														<?php endif; ?>
														<span class="question-author-action">
															<?php if($a['type'] == 'individual'): ?>
															<h4><?php echo e($a['person_name']); ?></h4>
															<?php else: ?>
															<h4><?php echo e($a['firm_name']); ?></h4>
															<?php endif; ?>
															<span><?php echo e(___ago($a['approve_date'])); ?></span>
														</span>
													</div>
													<div class="post-link">
														<?php if(!empty(\Auth::user())): ?>
														<a href="javascript:;" onclick="addReply(<?php echo e($a['id_answer']); ?>);" class="reply-answer">Post Answer</a>
														<?php endif; ?>
													</div>
													<div class="dnt-touch">
														<?php 
														$upvote = '';
														$downvote = '';
														if($a['saved_answer_vote'] == 'upvote'){
															$upvote = 'active';
														}elseif($a['saved_answer_vote'] == 'downvote'){
															$downvote = 'active';
														}else{
															$upvote = '';
															$downvote = '';
														}
														 ?>
														
														<a href="javascript:void(0)" class="upvote <?php echo e($upvote); ?>"  data-request="up-vote-answer" data-url="<?php echo e(url(sprintf('/mynetworks/upvote_answer?answer_id=%s',$a['id_answer']))); ?>">Upvote <span id="upvote_count_<?php echo e($a['id_answer']); ?>"><?php echo e($a['answer_upvote_count']); ?></span>
														</a>
														<a href="javascript:void(0)" class="downvote <?php echo e($downvote); ?>"  data-request="down-vote-answer" data-url="<?php echo e(url(sprintf('/mynetworks/downvote_answer?answer_id=%s',$a['id_answer']))); ?>">Downvote <span id="downvote_count_<?php echo e($a['id_answer']); ?>"><?php echo e($a['answer_downvote_count']); ?></span>
														</a>
													</div>
													
													<?php if(\Auth::user() && \Auth::user()->id_user != $a['id_user']): ?>
													<div class="forum-follow-detail">
														<?php 
														if($a['is_following'] == 1){
															$comment_is_following = 'active';
															$comment_follow_text  = 'Following';
														}else{
															$comment_is_following = '';
															$comment_follow_text  = 'Follow';
														}
														 ?>
														<a href="javascript:void(0);" class="follow-icon follow_user_<?php echo e($a['id_user'].' '.$comment_is_following); ?>" data-user_id="<?php echo e($a['id_user']); ?>" data-request="home-follow-user" data-url="<?php echo e(url(sprintf('/mynetworks/community/follow-user?user_id=%s',$a['id_user']))); ?>"><?php echo e($comment_follow_text); ?>

														</a>
													</div>
													<?php endif; ?>                                            
												</span>
											</div>

											
											<?php if($a['has_child'] == 1): ?>
											<ul class="subcomment-wrapper answer-chat">
												<?php $__currentLoopData = $a['has_child_answer']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getFirstLoop(); ?>
												<li class="subcomment">
													<div class="answer-wrapper question-listing">
														<div class="answer-level">
															<p> <?php echo e($value['answer_description']); ?></p>
															<div class="question-author listing-author-wrapper question-listing">
																<div class="flex-cell">
																	<?php if(!empty($value['filename'])): ?>
																	<img src="<?php echo e(asset($value['filename'])); ?>" alt="image" class="question-author-image">
																	<?php else: ?>
																	<img src="<?php echo e(asset('images/sdf.png')); ?>" alt="image" class="question-author-image">
																	<?php endif; ?>
																	<span class="question-author-action">
																		<?php if($value['type'] == 'individual'): ?>
																		<h4><?php echo e($value['person_name']); ?></h4>
																		<?php else: ?>
																		<h4><?php echo e($value['firm_name']); ?></h4>
																		<?php endif; ?>
																		<span><?php echo e(___ago($value['created'])); ?></span>
																	</span>
																</div>
																<div class="dnt-touch">
																	<?php 
																	$sub_upvote = '';
																	$sub_downvote = '';
																	if($value['saved_answer_vote'] == 'upvote'){
																		$sub_upvote = 'active';
																	}elseif($value['saved_answer_vote'] == 'downvote'){
																		$sub_downvote = 'active';
																	}else{
																		$sub_upvote = '';
																		$sub_downvote = '';
																	}
																	 ?>
																	
																	<a href="javascript:void(0)" class="upvote <?php echo e($sub_upvote); ?>"  data-request="up-vote-answer" data-url="<?php echo e(url(sprintf('/mynetworks/upvote_answer?answer_id=%s',$value['id_answer']))); ?>">Upvote <span id="upvote_count_<?php echo e($value['id_answer']); ?>"><?php echo e($value['answer_upvote_count']); ?></span>
																	</a>
																	<a href="javascript:void(0)" class="downvote <?php echo e($sub_downvote); ?>"  data-request="down-vote-answer" data-url="<?php echo e(url(sprintf('/mynetworks/downvote_answer?answer_id=%s',$value['id_answer']))); ?>">Downvote <span id="downvote_count_<?php echo e($value['id_answer']); ?>"><?php echo e($value['answer_downvote_count']); ?></span>
																	</a>
																</div>
																<?php if(\Auth::user() && \Auth::user()->id_user != $value['id_user']): ?>
																<div class="forum-follow-detail">
																	<?php 
																	if($value['is_following'] == 1){
																		$sub_comment_is_following = 'active';
																		$sub_comment_follow_text  = 'Following';
																	}else{
																		$sub_comment_is_following = '';
																		$sub_comment_follow_text  = 'Follow';
																	}
																	 ?>
																	<a href="javascript:void(0);" class="follow-icon follow_user_<?php echo e($value['id_user'].' '.$sub_comment_is_following); ?>" data-user_id="<?php echo e($value['id_user']); ?>" data-request="home-follow-user" data-url="<?php echo e(url(sprintf('/mynetworks/community/follow-user?user_id=%s',$value['id_user']))); ?>"><?php echo e($sub_comment_follow_text); ?>

																	</a>
																</div>
																<?php endif; ?>
															</div>
														</div>
													</div>
												</li>            
												<?php endforeach; $__env->popLoop(); $loop = $__env->getFirstLoop(); ?>
											</ul>
											<a href="javascript:;" onclick="loadReply(<?php echo e($a['id_answer']); ?>)" class="reply-answer" style="display:none;">| View reply</a>
											<?php endif; ?>
										</div>
										<div id="add-reply-response-<?php echo e($a['id_answer']); ?>"></div>
										<div id="reply-area-<?php echo e($a['id_answer']); ?>"></div>
									</li>
									<?php if(!empty(\Auth::user())): ?>
									<div id="text-reply-area-<?php echo e($a['id_answer']); ?>" style="display: none;">
										<div class="questionform-box">
											<h2 class="form-heading"><?php echo e(trans('website.W0451')); ?></h2>
											<div class="form-element form-group big-textarea" id="text-reply-area2-<?php echo e($a['id_answer']); ?>">
												<textarea id="answer_description_<?php echo e($a['id_answer']); ?>" name="answer_description_<?php echo e($a['id_answer']); ?>" class="form-control" placeholder="Enter Your Answer"></textarea>
												<span id="text-reply-error-area-<?php echo e($a['id_answer']); ?>"></span>
											</div>
											<?php if($company_profile != 'individual'): ?>
											<div class="form-group form-element">
												<div>
													<select name="answer_type_<?php echo e($a['id_answer']); ?>">
														<option value="individual" selected="selected">Post as <?php echo e(\Auth::user()->name); ?></option>
														<option value="firm">Post as firm</option>
													</select>                                               
												</div>
											</div>
											<?php else: ?>
											<div class="form-group form-element" style="display:none;">
												<div>
													<select name="answer_type_<?php echo e($a['id_answer']); ?>">
														<option value="individual" selected="selected">Post as <?php echo e(\Auth::user()->name); ?></option>
													</select>                                               
												</div>
											</div>
											<?php endif; ?>
											<div class="row form-group button-group">
												<div class="col-md-5 col-sm-5 col-xs-6 form-btn-set submit-solution">
													<input onclick="insertReply(<?php echo e($a['id_answer']); ?>);" type="button" class="button" value="<?php echo e(trans('website.W0393')); ?>" />
												</div>
											</div>
										</div>
									</div>
									<br/>
									<?php endif; ?>
									<?php endforeach; $__env->popLoop(); $loop = $__env->getFirstLoop(); ?>
								</ul>
							</div>
						</div>
					</div>
					<div class="col-md-4 col-sm-4 col-xs-12">
						<div class="related-questions">
							<div class="search-question-form">
								<h3 class="form-heading top-margin-20px"><?php echo e(trans('website.W0949')); ?></h3>
								<form method="get" action="<?php echo e(url('network/community/forum')); ?>" class="form-inline align-center">
										<!-- <div class="form-group custom-class">
											<input type="text" name="search_question" class="form-control custom-box" placeholder="Enter to search">
											<button type="submit" class="btn btn-default">Search</button>
										</div> -->
										<div class="search-wrapper detail-search-wrapper">
											<input type="search" name="search_question" class="form-control" placeholder="Enter to search">
											<buttton class="btn button">
												<img src="<?php echo e(asset('images/white-search-icon.png')); ?>">
											</buttton>
										</div>
									</form>           
								</div>
								<?php if(!empty(\Auth::user())): ?>
								<div class="first-question-section">
									<p><?php echo e(trans('website.W0963')); ?></p>
									<a href="<?php echo e(url('/network/community/forum/question/ask')); ?>" class="button bottom-margin-10px inline"><?php echo e(trans('website.W0953')); ?></a>
								</div>
								<?php endif; ?>
								<div class="other-question-section">
									<?php if(!empty($related_question)): ?>
									<h3 class="form-heading"><?php echo e(trans('website.W0954')); ?></h3>
									<ul>
										<?php $__currentLoopData = $related_question; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getFirstLoop(); ?>
										<li>
											<a href="<?php echo e(url('network/community/forum/question/'.___encrypt($r['id_question']))); ?>"><h4><?php echo e($r['question_description']); ?></h4>
											</a>
										</li>
										<?php endforeach; $__env->popLoop(); $loop = $__env->getFirstLoop(); ?>
									</ul>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section> 
		</div>
		<!-- /Main Content -->
		<input type="hidden" id="add-reply" value="<?php echo e(url('/network/community/forum/answer/add/'.$id_question)); ?>" />
		<input type="hidden" id="list-reply" value="<?php echo e(url('/network/community/forum/load/answer/'.$id_question)); ?>" />
		<?php $__env->stopSection(); ?>
<?php echo $__env->make($extends, array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>