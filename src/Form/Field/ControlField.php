<?php

namespace Redaxo\Core\Form\Field;

use Redaxo\Core\Form\AbstractForm;
use Redaxo\Core\Translation\I18n;
use rex_fragment;

use function count;
use function is_object;

class ControlField extends BaseField
{
    /** @var BaseField|null */
    private $saveElement;
    /** @var BaseField|null */
    private $applyElement;
    /** @var BaseField|null */
    private $deleteElement;
    /** @var BaseField|null */
    private $resetElement;
    /** @var BaseField|null */
    private $abortElement;

    public function __construct(AbstractForm $form, ?BaseField $saveElement = null, ?BaseField $applyElement = null, ?BaseField $deleteElement = null, ?BaseField $resetElement = null, ?BaseField $abortElement = null)
    {
        parent::__construct('', $form);

        $this->saveElement = $saveElement;
        $this->applyElement = $applyElement;
        $this->deleteElement = $deleteElement;
        $this->resetElement = $resetElement;
        $this->abortElement = $abortElement;
    }

    /**
     * @return string
     */
    protected function _get()
    {
        $s = '';
        $elements = [];

        if ($this->saveElement) {
            if (!$this->saveElement->hasAttribute('class')) {
                $this->saveElement->setAttribute('class', 'btn btn-save rex-form-aligned');
            }

            $e = [];
            $e['field'] = $this->saveElement->formatElement();
            $elements[] = $e;
        }

        if ($this->applyElement) {
            if (!$this->applyElement->hasAttribute('class')) {
                $this->applyElement->setAttribute('class', 'btn btn-apply');
            }

            $e = [];
            $e['field'] = $this->applyElement->formatElement();
            $elements[] = $e;
        }

        if ($this->abortElement) {
            if (!$this->abortElement->hasAttribute('class')) {
                $this->abortElement->setAttribute('class', 'btn btn-abort');
            }

            $e = [];
            $e['field'] = $this->abortElement->formatElement();
            $elements[] = $e;
        }

        if ($this->deleteElement) {
            if (!$this->deleteElement->hasAttribute('class')) {
                $this->deleteElement->setAttribute('class', 'btn btn-delete');
            }

            if (!$this->deleteElement->hasAttribute('onclick')) {
                $this->deleteElement->setAttribute('data-confirm', I18n::msg('form_delete') . '?');
            }

            $e = [];
            $e['field'] = $this->deleteElement->formatElement();
            $elements[] = $e;
        }

        if ($this->resetElement) {
            if (!$this->resetElement->hasAttribute('class')) {
                $this->resetElement->setAttribute('class', 'btn btn-reset');
            }

            if (!$this->resetElement->hasAttribute('onclick')) {
                $this->resetElement->setAttribute('data-confirm', I18n::msg('form_reset') . '?');
            }

            $e = [];
            $e['field'] = $this->resetElement->formatElement();
            $elements[] = $e;
        }

        if (count($elements) > 0) {
            $fragment = new rex_fragment();
            $fragment->setVar('elements', $elements, false);
            $s = $fragment->parse('core/form/submit.php');
        }

        return $s;
    }

    /**
     * @return bool
     */
    public function submitted($element)
    {
        return is_object($element) && '' != rex_post($element->getAttribute('name'), 'string');
    }

    /**
     * @return bool
     */
    public function saved()
    {
        return $this->submitted($this->saveElement);
    }

    /**
     * @return bool
     */
    public function applied()
    {
        return $this->submitted($this->applyElement);
    }

    /**
     * @return bool
     */
    public function deleted()
    {
        return $this->submitted($this->deleteElement);
    }

    /**
     * @return bool
     */
    public function resetted()
    {
        return $this->submitted($this->resetElement);
    }

    /**
     * @return bool
     */
    public function aborted()
    {
        return $this->submitted($this->abortElement);
    }
}