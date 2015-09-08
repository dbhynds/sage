(function($) {
  $(document).ready(function(){
    $('#order-components').addClass('win').sortable({
      containment: 'parent',
      handle: '.sortable',
      items: '> .component',
    });
  });
})(jQuery);