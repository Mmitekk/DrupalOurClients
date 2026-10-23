<?php

namespace Drupal\our_clients\Form;

use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides the slide deletion confirmation form.
 *
 * The image usage cleanup is performed in Slide::preDelete().
 */
class SlideDeleteForm extends EntityDeleteForm {

}
