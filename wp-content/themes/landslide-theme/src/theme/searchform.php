<?php // Search Form 
$search_query = get_search_query(); ?>
<search>
	<form class="search" method="get" action="<?php echo home_url(); ?>" role="search">			
		<input class="search-input" type="search" name="s" aria-label="Search for" value="<?php echo $search_query; ?>" placeholder="Search for...">	
		<button class="search-submit button" type="submit">Search</button>
	</form>
</search>