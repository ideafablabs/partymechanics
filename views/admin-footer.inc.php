	<div class="iflpm-footer">
		<?php if (class_exists('MovieQuotes')) {
			$q = MovieQuotes::get_random_movie_quote();			
			echo '<p class="movie-quote">'.$q->quote.' &mdash; <em>'.$q->movie_name.'</em></p>';
		} ?>
	</div>
</div>
