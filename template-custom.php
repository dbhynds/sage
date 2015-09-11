<?php
/**
 * Template Name: Custom Template
 */

while (have_posts()) :
	the_post();
	Components\build();
endwhile;
// require('index.php');