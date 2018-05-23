<?php

final class PhabricatorStandardCustomFieldDateText
    extends PhabricatorStandardCustomField {

    public function getFieldType() {
        return 'date_text';
    }

    public function buildFieldIndexes() {
        $indexes = array();

        $value = $this->getFieldValue();
        if (strlen($value)) {
            $indexes[] = $this->newStringIndex($value);
        }

        return $indexes;
    }

    public function readApplicationSearchValueFromRequest(
        PhabricatorApplicationSearchEngine $engine,
        AphrontRequest $request) {

        return $request->getStr($this->getFieldKey());
    }

    public function applyApplicationSearchConstraintToQuery(
        PhabricatorApplicationSearchEngine $engine,
        PhabricatorCursorPagedPolicyAwareQuery $query,
        $value) {

        if (strlen($value)) {
            $query->withApplicationSearchContainsConstraint(
                $this->newStringIndex(null),
                $value);
        }
    }

    public function appendToApplicationSearchForm(
        PhabricatorApplicationSearchEngine $engine,
        AphrontFormView $form,
        $value) {

        $form->appendChild(
            id(new AphrontFormDateTextControl())
                ->setLabel($this->getFieldName())
                ->setName($this->getFieldKey())
                ->setValue($value));
    }

    public function shouldAppearInHerald() {
        return true;
    }

    public function getHeraldFieldConditions() {
        return array(
            HeraldAdapter::CONDITION_CONTAINS,
            HeraldAdapter::CONDITION_NOT_CONTAINS,
            HeraldAdapter::CONDITION_IS,
            HeraldAdapter::CONDITION_IS_NOT,
            HeraldAdapter::CONDITION_REGEXP,
            HeraldAdapter::CONDITION_NOT_REGEXP,
        );
    }

    public function renderEditControl(array $handles) {
//        if(empty($this->getFieldValue())){
//            $this->setFieldValue(date('Y-m-d h:i:s'));
//        }

//        $this->getViewer();
       $text_control = id(new AphrontFormDateTextControl())
            ->setDisableAutocomplete(true)
            ->setName($this->getFieldKey())
            ->setCaption($this->getCaption())
            ->setValue($this->getFieldValue())
            ->setError($this->getFieldError())
            ->setLabel($this->getFieldName())
            ->setPlaceholder($this->getPlaceholder())
        ;
//        $text_control->setEnabled(false);

//        $text_control = id(new AphrontFormDateTextControl)
//            ->setName($this->getFieldKey())
//            ->setCaption($this->getCaption())
//            ->setValue($this->getFieldValue())
//            ->setError($this->getFieldError())
//            ->setLabel($this->getFieldName());
        return $text_control;
    }


    public function getHeraldFieldStandardType() {
        return HeraldField::STANDARD_TEXT;
    }

    protected function getHTTPParameterType() {
        return new AphrontStringHTTPParameterType();
    }

    public function getConduitEditParameterType() {
        return new ConduitStringParameterType();
    }

    public function renderPropertyViewValue(array $handles) {
        if (!strlen($this->getFieldValue())) {
            return "nothing";
        }
//        $transactions = id(new ManiphestTransactionQuery())
//            ->setViewer($this->getViewer())
//            ->withObjectPHIDs()
//            ->needComments(true)
//            ->execute();




        return id($this->getFieldValue())."  ".$this->getViewer()." ".current($handles);
//        return id($this->getObject()->getPHID());
    }

    public function readValueFromRequest(AphrontRequest $request) {
        $value = $request->getStr($this->getFieldKey());
        if (!strlen($value)) {
            $value = null;
        }
        $this->setFieldValue($value);
    }

    public function getApplicationTransactionTitle(
        PhabricatorApplicationTransaction $xaction) {
        $author_phid = $xaction->getAuthorPHID();
        $old = $xaction->getOldValue();
        $new = $xaction->getNewValue();
//
//        $old = idx($this->getOptions(), $old, $old);
//        $new = idx($this->getOptions(), $new, $new);


        return pht(
            '%s CHANGED %s FROM %s TO %s.',
            $xaction->renderHandleLink($author_phid),
            $this->getFieldName(),
            $old !== null ? $old : 'null',
            $new !== null ? $new : 'null');
    }

}
