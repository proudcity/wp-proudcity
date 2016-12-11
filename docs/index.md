ProudCity WordPress
===================

[ProudCity](https://proudcity.com) is a Wordpress platform for modern, standards-compliant municipal websites. [Find your city's demo](https://proudcity.com/start) or [view an example website](https://example.proudcity.com).

Documentation sections

* Overview
* [Installing](installing.md)
* [Developing](developing.md)
* [Docker](docker.md)
* [Kubernetes](kubernetes.md)


## Contributing

This repo contains a composer make file that will build a complete Wordpress installation.  See [Composer](#composer) for more details.  Most of the actual code is in our individual Plugin and Theme repos:
* [wp-proud-theme](https://github.com/proudcity/wp-proud-theme): The ProudCity Wordpress theme built on top of [Bootstrap](http://getbootstrap.com) and [Sage](https://roots.io/sage/).
* [wp-proud-core](https://github.com/proudcity/wp-proud-core): Core customizations and helper modules, including proud-teaser-list, which creates easy, filterable lists of content.
* [wp-proud-admin](https://github.com/proudcity/wp-proud-admin): Administration theme, permissions tweaks, and ProudCity settings pages.
* [wp-proud-search](https://github.com/proudcity/wp-proud-search): Provides title suggestions while typing a search query.
* [wp-proud-agency](https://github.com/proudcity/wp-proud-agency): Creates an Agency post type. 
* [wp-proud-document](https://github.com/proudcity/wp-proud-document): Creates a Document post type for file uploads and forms.
* [wp-proud-payment](https://github.com/proudcity/wp-proud-payment): Creates a Payment post type.
* [wp-proud-actions-app](https://github.com/proudcity/wp-proud-actions-app): An interactive, Angular-based 311 interface for FAQ, Payments, Issue reporting and Issue lookup.
* [wp-proud-social-app](https://github.com/proudcity/wp-proud-social-app): Social media feed that pulls from an aggregated JSON feed and can display media as a wall, timeline, or simple feed. 
* [wp-proud-map-app](https://github.com/proudcity/wp-proud-map-app): Creates an interactive city services map with [Mapbox](http://mapbox.com).

All bug reports, feature requests and other issues should be added to the [wp-proudcity Issue Queue](https://github.com/proudcity/wp-proudcity/issues).  If you are using ProudCity, or are interested in getting involved, please join our [Community Forum](https://groups.google.com/d/forum/proudcitydevelopers). 

Visit [our website](https://proudcity.com/developers) for more information about ProudCity for developers.