How to add a new page ? (code is between ##########)

First, add a file in the model/ directory. This file have to be called ACTION.php where ACTION is used here : index.php?action=ACTION
This file is composed of two parts :
- Protection :
################################################
if( !defined('_INDEX') ) {
	header('HTTP/1.1 403 Forbidden');
	exit;
}
################################################
- Class :
	It must be called ACTION example : index.php?action=register => register.php => class Register
	This class must inherit of the superclass Model
	Only one method is obligatory :
		function getView($params, $synchrone);

	Explaination of the method :
		getView is the function which return the body of the page. It is called with the params too and can use everything. The body of the page must be returned by this function.

Now how to display the content ?

There is a file in the view/ directory : ACTION.html. You must create and use it for your page if it print anything. You have for the moment 3 extensions for your file depending on the content :
	.xml : eXtended Modeling Language
	.json : JavaScript Object N...
	.html : HyperText Modeling Language

You can have the 3 files if you need it !
In your file model/ACTION.php, you can call this page with :
###############################################
(ViewItem) View::setFile(ACTION, View::HTML_FILE);
###############################################

View::HTML_FILE is a constant for file ACTION.html, you also have View::JSON_FILE and View::XML_FILE for ACTION.json and ACTION.xml respectively.

Finally, in order to have the header and the footer generals, call the method
###############################################
(String) parent::setBody($content);
###############################################
Where $content is the content of the page you generated or the ViewItem directly. cf documentation about ViewItem.


How to implement language system ?

It's the last file to add : in each dir in lang/ dir, you can add a file called ACTION.ini. It may loog like :
###############################################
[main]
key = "value"

[whatever you want]
key = "value"
###############################################

You can separate it in all the sections you want, represented by the tag [section name] in order to classify your lang keys. For example you can have a section [error].

To summarize : index.php?action=ACTION => model/ACTION.php => view/ACTION.html (or) view/ACTION.json (or) view/ACTION.xml => lang/*/ACTION.ini