/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
   config.toolbar = [
   [ 'Link','Unlink','Anchor'  ],
   '/',
   ['Undo','Redo','-','Cut','Copy','Paste','Find','Replace','-','Outdent','Indent','-','Print'],
   '/',
   ['Bold','Italic','Underline','StrikeThrough'],
   '/',
   ['NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']];
   config.forcePasteAsPlainText = true;
};