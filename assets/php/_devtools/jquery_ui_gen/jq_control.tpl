<?php
	/**
	 * <%= $objJqDoc->strQcClass %>Gen File
	 * 
	 * The abstract <%= $objJqDoc->strQcClass %>Gen class defined here is
	 * code-generated and contains options, events and methods scraped from the
	 * JQuery UI documentation Web site. It is not generated by the typical
	 * codegen process, but rather is generated periodically by the core QCubed
	 * team and checked in. However, the code to generate this file is
	 * in the assets/_core/php/_devetools/jquery_ui_gen/jq_control_gen.php file
	 * and you can regenerate the files if you need to.
	 *
	 * The comments in this file are taken from the JQuery UI site, so they do
	 * not always make sense with regard to QCubed. They are simply provided
	 * as reference. Note that this is very low-level code, and does not always
	 * update QCubed state variables. See the <%= $objJqDoc->strQcClass %>Base 
	 * file, which contains code to interface between this generated file and QCubed.
	 *
	 * Because subsequent re-code generations will overwrite any changes to this
	 * file, you should leave this file unaltered to prevent yourself from losing
	 * any information or code changes.  All customizations should be done by
	 * overriding existing or implementing new methods, properties and variables
	 * in the <%= $objJqDoc->strQcClass %> class file.
	 *
	 */

	/* Custom event classes for this control */
	
	
<% foreach ($objJqDoc->events as $event) { %>
	/**
	 * <%= str_replace("\n", "\n\t * ", wordwrap(trim($event->description), $objJqDoc->descriptionLine, "\n\t\t")) %>
	 */
	class <%= $event->eventClassName %> extends QJqUiEvent {
		const EventName = '<%= $event->eventName %>';
	}
<% } %>

	/* Custom "property" event classes for this control */
<% foreach ($objJqDoc->options as $option) { %>
	<% if ($option instanceof Event) { %>
	/**
	 * <%= str_replace("\n", "\n\t * ", wordwrap(trim($option->description), $objJqDoc->descriptionLine, "\n\t\t")) %>
	 */
	class <%= $option->eventClassName %> extends QJqUiPropertyEvent {
		const EventName = '<%= $option->eventName %>';
		protected $strJqProperty = '<%= $option->name %>';
	}

	<% } %>
<% } %>

	/**
	 * Generated <%= $objJqDoc->strQcClass %>Gen class.
	 * 
	 * This is the <%= $objJqDoc->strQcClass %>Gen class which is automatically generated
	 * by scraping the JQuery UI documentation website. As such, it includes all the options
	 * as listed by the JQuery UI website, which may or may not be appropriate for QCubed. See
	 * the <%= $objJqDoc->strQcClass %>Base class for any glue code to make this class more
	 * usable in QCubed.
	 * 
	 * @see <%= $objJqDoc->strQcClass %>Base
	 * @package Controls\Base
<% foreach ($objJqDoc->options as $option) { %>
	 * @property <%= $option->phpType %> $<%= $option->propName %> <%= str_replace("\n", "\n\t * ", wordwrap(trim($option->description), $objJqDoc->descriptionLine, "\n\t\t")) %>
<% } %>
	 */

	<%= $objJqDoc->strAbstract %>class <%= $objJqDoc->strQcClass %>Gen extends <%= $objJqDoc->strQcBaseClass %>	{
		protected $strJavaScripts = __JQUERY_EFFECTS__;
		protected $strStyleSheets = __JQUERY_CSS__;
<% foreach ($objJqDoc->options as $option) { %>
		/** @var <%= $option->phpType %> */
	<% if (!$option->defaultValue) { %>
		protected $<%= $option->varName %>;
	<% } %>
	<% if ($option->defaultValue) { %>
		protected $<%= $option->varName %> = null;
	<% } %>
<% } %>
		
		protected function makeJsProperty($strProp, $strKey) {
			$objValue = $this->$strProp;
			if (null === $objValue) {
				return '';
			}

			return $strKey . ': ' . JavaScriptHelper::toJsObject($objValue) . ', ';
		}

		protected function makeJqOptions() {
<% if (method_exists($objJqDoc->strQcBaseClass, 'makeJqOptions')) { %>
			$strJqOptions = parent::makeJqOptions();
			if ($strJqOptions) $strJqOptions .= ', ';
<% } %>
<% if (!method_exists($objJqDoc->strQcBaseClass, 'makeJqOptions')) { %>
			$strJqOptions = '';
<% } %>
<% foreach ($objJqDoc->options as $option) { %>
			$strJqOptions .= $this->makeJsProperty('<%= $option->propName %>', '<%= $option->name %>');
<% } %>
			if ($strJqOptions) $strJqOptions = substr($strJqOptions, 0, -2);
			return $strJqOptions;
		}

		public function getJqSetupFunction() {
			return '<%= $objJqDoc->strJqSetupFunc %>';
		}

		public function GetControlJavaScript() {
			return sprintf('jQuery("#%s").%s({%s})', $this->getJqControlId(), $this->getJqSetupFunction(), $this->makeJqOptions());
		}

		public function GetEndScript() {
			$str = '';
			if ($this->getJqControlId() !== $this->ControlId) {
				// #845: if the element receiving the jQuery UI events is different than this control
				// we need to clean-up the previously attached event handlers, so that they are not duplicated 
				// during the next ajax update which replaces this control.
				$str = sprintf('jQuery("#%s").off(); ', $this->getJqControlId());
			}
			$str .= $this->GetControlJavaScript();
			if ($strParentScript = parent::GetEndScript()) {
				$str .= '; ' . $strParentScript;
			}
			return $str;
		}
		
		/**
		 * Call a JQuery UI Method on the object. 
		 * 
		 * A helper function to call a jQuery UI Method. Takes variable number of arguments.
		 *
		 * @param boolean $blnAttribute true if the method is modifying an option, false if executing a command
		 * @param string $strMethodName the method name to call
		 * @internal param $mixed [optional] $mixParam1
		 * @internal param $mixed [optional] $mixParam2
		 */
		protected function CallJqUiMethod($blnAttribute, $strMethodName /*, ... */) {
			$args = func_get_args();
			array_shift ($args);

			$strArgs = JavaScriptHelper::toJsObject($args);
			$strJs = sprintf('jQuery("#%s").%s(%s)',
				$this->getJqControlId(),
				$this->getJqSetupFunction(),
				substr($strArgs, 1, strlen($strArgs)-2));	// params without brackets
			if ($blnAttribute) {
				$this->AddAttributeScript($strJs);
			} else {
				QApplication::ExecuteJavaScript($strJs);
			}
		}


<% foreach ($objJqDoc->methods as $method) { %>
		/**
		 * <%= str_replace("\n", "\n\t\t * ", wordwrap(trim($method->description))) %>
<% foreach ($method->requiredArgs as $reqArg) { %>
    <% if ($reqArg{0} != '"') { %>
		 * @param <%= $reqArg %>
    <% } %>
<% } %>
<% foreach ($method->optionalArgs as $optArg) { %>
		 * @param <%= $optArg %>
<% } %>
		 */
		public function <%= $method->phpSignature %> {<%  
				$args = array();
				foreach ($method->requiredArgs as $reqArg) {
					$args[] = $reqArg;
				}
				foreach ($method->optionalArgs as $optArg) {
					$args[] = $optArg;
				}
				$strArgs = join(", ", $args); %>
			$this->CallJqUiMethod(false, <%= $strArgs; %>);
		}
<% } %>


		public function __get($strName) {
			switch ($strName) {
<% foreach ($objJqDoc->options as $option) { %>
				case '<%= $option->propName %>': return $this-><%= $option->varName %>;
<% } %>
				default: 
					try { 
						return parent::__get($strName); 
					} catch (QCallerException $objExc) { 
						$objExc->IncrementOffset(); 
						throw $objExc; 
					}
			}
		}

		public function __set($strName, $mixValue) {
			switch ($strName) {
<% foreach ($objJqDoc->options as $option) { %>
				case '<%= $option->propName %>':
	<% if (!$option->phpQType) { %>
					$this-><%= $option->varName %> = $mixValue;
	<% if (!($option instanceof Event)) { %>
				
					if ($this->OnPage) {
						$this->CallJqUiMethod(true, 'option', '<%= $option->name %>', $mixValue);
					}
					break;
	<% } %>
	<% } %>
	<% if ($option->phpQType) { %>
					try {
	<% if (!($option instanceof Event)) { %>
						$this-><%= $option->varName %> = QType::Cast($mixValue, <%= $option->phpQType %>);
						if ($this->OnPage) {
							$this->CallJqUiMethod(true, 'option', '<%= $option->name %>', $this-><%= $option->varName %>);
						}
	<% } %>
	<% if ($option instanceof Event) { %>
						$this-><%= $option->varName %> = new QJsClosure($mixValue, array("<%= join('","', $option->arrArgs) %>"));
	<% } %>
						break;
					} catch (QInvalidCastException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
	<% } %>

<% } %>

<% if ($objJqDoc->hasDisabledProperty) { %>
				case 'Enabled':
					$this->Disabled = !$mixValue;	// Tie in standard QCubed functionality
					parent::__set($strName, $mixValue);
					break;
					
<% } %>
				default:
					try {
						parent::__set($strName, $mixValue);
						break;
					} catch (QCallerException $objExc) {
						$objExc->IncrementOffset();
						throw $objExc;
					}
			}
		}

		/**
		* If this control is attachable to a codegenerated control in a metacontrol, this function will be
		* used by the metacontrol designer dialog to display a list of options for the control.
		* @return QMetaParam[]
		**/
		public static function GetMetaParams() {
			return array_merge(parent::GetMetaParams(), array(
<% foreach ($objJqDoc->options as $option) { %>
	<% if ($option->phpQType) { %>
				new QMetaParam (get_called_class(), '<%= $option->propName %>', '<%= addslashes(trim($option->description)) %>', <%= $option->phpQType %>),
	<% } %>
<% } %>			));
		}
	}

?>
