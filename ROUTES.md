# Application Routes
Rough draft of expected application routes. This has been drafted to conceptualize interfaces that will be needed. This list is **incomplete**. Routes that may not need interfaces are indicated with an asterisk (*).

## Landing

    /landing                                        Community Voices landing page. Includes slideshow

## User Management
These routes are likely accessibly from the application's header toolbar.

    /users/register
    /users/login
    /users/logout
    /users/:id                                      Lookup user with id
    /users/:id/edit                                 Edit user with id

## Application Main
The following routes are accessible from the application's main interface. The first three routes in the first three route clusters will have very similarly-styled interfaces (if not exactly the same aside from minor differences).

    /slides[?sort=]                                 Lists all slides
    /slides/tagged/:tag[?sort=]                     Lists all slides with tag
    /slides/content-category/:category[?sort=]      Lists all slides with content category
    /slides/:id                                     Lookup slide with id
    /slides/:id/edit                                Edit slide with id
    /slides/:id/delete                              Delete slide with id
    /slides/new                                     Create a new slide

    /images[?sort=]                                 Lists all images
    /images/tagged/:tag[?sort=]                     Lists all images with tag
    /images/content-category/:category[?sort=]      Lists all images with content category
    /images/:id                                     Lookup image with id
    /images/:id/edit                                Edit image with id
    /images/:id/Delete                              Delete image with id
    /images/new                                     Create a new image

    /quotes[?sort=]                                 Lists all quotes
    /quotes/tagged/:tag[?sort=]                     Lists all quotes with tag
    /quotes/content-category/:category[?sort=]      Lists all quotes with content category
    /quotes/:id                                     Lookup quote with id
    /quotes/:id/edit                                Edit quote with id
    /quotes/:id/Delete                              Delete quote with id
    /quotes/new                                     Create a new quote

    /search                                         Media search landing page
    /search/?q=[&sort=]                             Searches media

## Application Management
The following routes are accessible from a management interface within the application. The management interface will be linked to somehow on the main interface. Most of these routes are only visible to users with higher-level privileges.

    /content-categories                             Lists all content categories
    /content-categories/:id                         *Lookup content category with id
    /content-categories/:id/edit                    Edit content category with id
    /content-categories/:id/delete                  Delete content category with id
    /content-categories/new                         Create a new content category

    /org-categories                                 Lists all org categories
    /org-categories/:id                             *Lookup org category with id
    /org-categories/:id/edit                        Edit org category with id
    /org-categories/:id/delete                      Delete org category with id
    /org-categories/new                             Create a new org category

    /tags                                           Lists all tags
    /tags/:id                                       *Lookup tags with id
    /tags/:id/edit                                  Edit tags with id
    /tags/:id/delete                                Delete tags with id
    /tags/new                                       Create a new tag
