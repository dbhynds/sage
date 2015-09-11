<?php

get_template_part('components/page', 'header');

while (have_posts()) :
	the_post();
	Components\build();
endwhile;

the_posts_navigation();