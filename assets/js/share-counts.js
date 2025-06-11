jQuery(function($){
  $(document).on('click', '.toptal-social-share-wrapper a', function(){
    var network = $(this).closest('div').attr('class').split(' ')[0];
    var postId = $(this).closest('.toptal-social-share-wrapper').data('post-id');
    if(!network || !postId) {
      return;
    }
    $.post(toptalShareCount.ajax_url, {action:'toptal_update_share_count', network:network, post_id:postId});
  });
});

