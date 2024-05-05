<?php
use function CatPaw\Web\redirect;
return fn () => // The user is loading page `/form` using perhaps the refresh button, 
                // we need to redirect back to the main form.
        redirect('/');