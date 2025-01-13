<?php // Search Form ?>

<form class="search" method="get" action="<?php echo home_url(); ?>" role="search">

	<label for="s" class="search-input-label visually-hidden">Search For</label>		
	<input class="search-input" type="search" name="s" aria-label="Search for" placeholder="Search for...">
		
	<button class="search-submit button" type="submit">Search</button>

</form>