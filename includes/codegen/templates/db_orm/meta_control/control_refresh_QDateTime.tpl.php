			if ($this-><?= $strControlId ?>) $this-><?= $strControlId ?>->DateTime = $this-><?= $strObjectName ?>-><?= $objColumn->PropertyName ?>;
			if ($this-><?= $strLabelId ?>) $this-><?= $strLabelId ?>->Text = sprintf($this-><?= $strObjectName ?>-><?= $objColumn->PropertyName ?>) ? $this-><?= $strObjectName ?>-><?= $objColumn->PropertyName ?>->qFormat($this->str<?= $objColumn->PropertyName ?>DateTimeFormat) : null;