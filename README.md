# Form Mode Manager [![Build Status](https://travis-ci.org/woprrr/form_mode_manager.svg?branch=8.x-1.x)](https://travis-ci.org/woprrr/form_mode_manager) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/woprrr/form_mode_manager/badges/quality-score.png?b=8.x-1.x)](https://scrutinizer-ci.com/g/woprrr/form_mode_manager/?branch=8.x-1.x) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/938b7347-7b09-42e2-8da9-56ad8df1432f/mini.png)](https://insight.sensiolabs.com/projects/938b7347-7b09-42e2-8da9-56ad8df1432f) [![Gitter](https://badges.gitter.im/gitterHQ/gitter.svg)](https://gitter.im/Form-mode-manager)

Provides an interface that allows the implementation and use of Form modes 
easily without specific development. This module provides different
configurations/routes/UI/Additional access control to simplify and enhance the
management and development of your different entity forms that allowing you 
to implement a complex content strategy in simple ways. Form mode manager 
provides a mechanism fully integrated with the event and routing system to 
support your custom content entities and flexibility to add some behaviors of 
entity forms.

## Table of contents

<details>

<!-- toc -->

- [Requirements](#requirements)
- [Roadmap](#roadmap)
- [Known problems](#known-problems)
- [Installation](#installation)
- [Usage](#usage)
    - [Create entities as a specific form mode](#create-entities-as-a-specific-form-mode)
    - [Edit entities as a specific form mode](#edit-entities-as-a-specific-form-mode)
    - [Advanced use cases](#advanced-use-cases)
- [Configurations](#configurations)
- [Try out the module](#try-out-the-module)
    - [Try on simplytest.me](#try-on-simplytest.me)

<!-- tocstop -->

</details>

## Requirements
* Drupal 8.

## Roadmap
You can follow the evolution of this module 
[here](https://www.drupal.org/node/2880651).

## Compatibility
[Here](https://www.drupal.org/node/2838003) you are a list of known compatible 
entities (standard & contributed).

## Known problems
* Profile :
Not compatible with Profile ATM please follow [#2834749] issue to have more 
precision.
* Comment : 
Need more specific adjust to work properly with Form mode manager. We need 
more volunteers to purpose a new sub-module to assume an acceptable 
compatibility follows [#2887326].
* Contact : 
Same problem as `Comment` entity, the process of `contact_message` are too 
specific for these entity we need more specific developments.

[#2834749]: https://www.drupal.org/node/2834749
[#2887326]: https://www.drupal.org/node/2887326

## Installation
1. Download and extract the module to your (`sites/all/modules/contrib`) 
folder.
2. Enable the module on the Drupal Modules page (`admin/modules`) or using 
(`$ drush en form_mode_manager`)

### Usage
To simplify the understanding of this documentation the example of use 
following are based on the most generic case (`Node`). Please go to 
[D.O documentation page] to found other common entity cases.

#### Create entities as a specific form mode
+ Create a mode form (`admin/structure/display-modes/form/add/node`) or 
programmatically with `targetEntityType` set to `node`.
+ Add the created form mode to a content type (article for example), go to 
(`admin/structure/types/manage/article/form-display`)
+ At the bottom we have a fieldset `Custom Display settings` with our form 
modes listed compatible with this entity.
+ Check a form mode and save the form.
+ Now we have two tabs at the top of the page (or a message purpose you a link 
in message area when we have a cached result of the page), `Default` and the 
label of your form mode newly created.
+ With these tabs you can define a form different as `default` to configure 
our form mode.
+ Now to use your form mode you have two ways
    - Go directly to (`node/add/article/form_mode_machine_name`).
    - Go to node overview page (`admin/content`) and use the new local action 
    link (`Add node as xxxx`).
#### Edit entities as a specific form mode
+ Display a node entity like (`/node/1`).
+ Click to edit as usual way.
+ Without any specific configuration you have a new tabs level at the bottom 
of (`Edit`) provided by default. These tabs permit you to use the `default` 
form mode or our other form mode activated to this Content Type.

You can also edit our entities directly on your overviews pages with 
operations links you can show directly all operations allowed to see` for the 
current user.

#### Advanced use cases 
In advance use cases you can need to restrict access of `default` form mode 
but only for a specific form mode(s) to a specific role.

To do that you need to configure form_mode_manager to change the location of 
your form mode local tasks at the primary level (Same level as `Edit` button) 
and restrict access to 'default' form for a specific role for that entity. 
You can also totally exclude a specific form_mode to all form mode manager 
process/enhancements.

@see [access control use cases](https://www.drupal.org/docs/8/modules/form-mode-manager/usages/access-control) for more information.

## Configurations
1. Depending on the role of the user, it may have access to a form mode, and 
not to the default of a given content type. In this case, go 
`admin/config/content/form_mode_manager/links-task` to configure the location 
of the task locals.
Example, for a content type "article" that to a form mode contributor, 
if the current user has only right to form mode contributor it will be 
necessary to change the position of the Links task so that it is at the 
first level instead of the Second as the default for nodes.

2. You also have the possibility to exclude form mode via the interface by 
going to the other tab of the parameter page here 
`admin/config/content/form_mode_manager`. You may notice that by default 
the form mode register of user is excluded by default in order to avoid any 
conflict.

## Custom entities

FormModeManager can be used in lots of contexts. Here you have all examples 
for Core entities examples, the implementation and configuration depend of 
your entity. If you define a custom entity and not respect the existent 
"pattern" you can be needing more developments to use Form mode manager 
because the discovery can't know all possible custom changes.

Here you have a small list of minimal configuration needed by Form mode 
manager to work with our entities :

+ Have a "standard" entity route name like for your operation form(s).
    - Route name for your add form operation like 
    (`entity.ENTITY_TYPE_ID.add_form`). 
    - Route name for your edit form operation like 
    (`entity.ENTITY_TYPE_ID.edit_form`).
+ Have the correct operation links :
    - `default` (to generate your add form)
    - `edit-form` (to generate your add form)

For more information about links templates see [Display mode (form mode)] 
or [Form mode manager usages page] to found examples of possible interactions 
with form_mode_manager module enhancements.

[Display mode (form mode)]: https://www.drupal.org/docs/8/api/entity-api/display-modes-view-modes-and-form-modes
[Form mode manager usages page]: https://www.drupal.org/docs/8/modules/form-mode-manager/usages

## Try out the module
You can try Form mode manager in action directly with the sub-module 
`Form mode manager example` to test different use cases. 
You can also install a module in your project and enable it 
`drush en form_mode_manager_examples -y` or use SimplyTest.me 
service like next part.

### Try on simplytest.me
You can Try Form mode manager with all features online.
1. You just need to follow the desired version by click on the button 
(Link already generated for you).
2. Lauch sandox.
3. Install online sandbox by following instructions.
4. Enable 'Form mode manager examples' submodule on 'Extension page' 
`admin/modules`
5. Try it now.
6. Use this link [1.0 (Release Candidate)].

[1.0 (Release Candidate)]: https://simplytest.me/project/form_mode_manager/8.x-1.0-rc1
[D.O documentation page]: https://www.drupal.org/docs/8/modules/form-mode-manager
