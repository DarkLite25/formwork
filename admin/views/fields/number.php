<?php if ($field->has('label')): ?><label for="<?= $field->name() ?>"><?= $field->label() ?></label><?php endif; ?>
<input type="number" id="<?= $field->name() ?>" name="<?= $field->name() ?>" min="<?= $field->get('min') ?>" max="<?= $field->get('max') ?>" step="<?= $field->get('step') ?>" value="<?= $field->value() ?>"<?= $field->get('required') ? ' required' : '' ?>>
