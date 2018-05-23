<?php

final class AphrontFormDateTextControl extends AphrontFormControl {

  private $disableAutocomplete;
  private $sigil;
  private $placeholder;
  private $enable = true;

  public function setDisableAutocomplete($disable) {
    $this->disableAutocomplete = $disable;
    return $this;
  }

  private function getDisableAutocomplete() {
    return $this->disableAutocomplete;
  }

  public function getPlaceholder() {
    return $this->placeholder;
  }

  public function setPlaceholder($placeholder) {
    $this->placeholder = $placeholder;
    return $this;
  }

  public function getSigil() {
    return $this->sigil;
  }

  public function setSigil($sigil) {
    $this->sigil = $sigil;
    return $this;
  }

  public function getEnabled() {
    return $this->enable;
  }

  public function setEnabled($enable) {
    $this->enable = $enable;
    return $this;
  }

  protected function getCustomControlClass() {
    return 'aphront-form-control-text';
  }

  protected function renderInput() {
    return javelin_tag(
      'input',
      array(
//        'type'         => 'hidden',
        'type'         => 'text',
        'name'         => $this->getName(),
        'value'        => $this->getValue(),
          'disabled'     => $this->getDisabled() ? 'disabled' : null,
        'autocomplete' => $this->getDisableAutocomplete() ? 'off' : null,
        'id'           => $this->getID(),
        'sigil'        => $this->getSigil(),
        'placeholder'  => $this->getPlaceholder(),
      ));
  }

}
