'use strict';

(function( win, doc ) { 

    var categoryList;
    var categoryFormNew, categoryCreateBlock


    window.addEventListener('load', function( event ) {
    
        // projects list

        categoryList = document.getElementById('projects-list');

        // load projects list

        populateProjectsList();

        // initialize popover category

        initializeCategoriesEvents();
    });


    function initializeCategoriesEvents() {

        // add events, new form project

        categoryFormNew = document.getElementById('form-project-new');

        categoryFormNew.addEventListener('submit', addProjectEvent);

        // category create new block

        var categoryCreateBlock = document.getElementById('project-create-block');

        delegate(categoryCreateBlock, '.button-project-create-popover, .button-project-cancel', 'click', function( event ){
            event.preventDefault();
            
            var target = event.target;

            // show popover :

            var popover = categoryCreateBlock.querySelector('#project-popover-new');

            if( popover ) {
                popover.classList.toggle('is-visible');
            }
        });

        // delete project

        delegate(categoryList, '.component-project .project-delete', 'click', deleteProjectEvent);

        // new form element

        delegate(categoryList, '.component-project-new', 'click', function( event ){
            event.preventDefault();

            toggleState(categoryFormNew, 'form', 'view');
        });

        // click outside project new
        // document.body.addEventListener('click', function(event){
        //     toggleState(categoryFormNew, 'view', 'view');
        // }, true);
    }





    function populateProjectsList() {

        categoryList.classList.add('projects-list-loading');

        var successHandler = function( response ) {

            categoryList.innerHTML = '';
            categoryList.classList.remove('projects-list-loading');

            for( var index in response ) {

                var project = response[index];

                project.background_url = '/img/project-bg-'+( project.id % 10 )+'.png'; // samir bg

                appendTemplate('project', categoryList, project);
            }
        };

        var errorHandler = function( status, exception ) {
            jserror('Impossible de récupérer les projets', status);
        };

        AjaxSimple('GET', api.endPoint+'projects/list', successHandler, errorHandler);
    }



    function addProjectEvent( event ) {

        var target = event.target;
    
        event.preventDefault(); // form

        var form = this;

        var fields = ['title','description','users','manager'];
        var formData = {};
    
        for( var i in fields ) {

            var field = fields[i];

            formData[field] = form[field].value; //.elements
        }

        // call ajax create
    
        var successHandler = function( response ) {
            var projectId = response.projectId;

            // set new visible
            toggleState(categoryFormNew, 'form', 'view');

            // add project to header

            var menuProjectsList = document.querySelector('#panel .menu-projects-list');
            if( menuProjectsList ) {
                var menuItem = document.createElement('div');
                menuItem.setAttribute('id', 'header-project-'+projectId);

                var prefix = document.createTextNode('- ');
                menuItem.appendChild(prefix);

                var menuItemLink = document.createElement('a');
                menuItemLink.setAttribute('href', '/dashboard/'+projectId);
                menuItemLink.innerText = formData['title'];

                menuItem.appendChild(menuItemLink);
                menuProjectsList.appendChild(menuItem);
            }

            // repopulate projets list
            populateProjectsList();
        };
    
        var errorHandler = function( status, exception ) {
            jserror('Impossible de créer ce projet', status);
        };

        AjaxSimple('POST', api.endPoint+'project/create', successHandler, errorHandler, formData);
    }

    function updateProjectEvent( event ) {
    
    }

    function deleteProjectEvent( event ) {
        event.preventDefault(); // button

        var target = event.target;

        var confirmDialog = confirm('Supprimer ce projet ?');
            
        if( !confirmDialog ) return;

        // find project block

        var projectBlock = findAncestor(target, '.component-project');
    
        var projectId = projectBlock.getAttribute('data-id');
    
        // call ajax delete

        categoryList.classList.add('projects-list-loading');
    
        var successHandler = function( response ) {

            categoryList.classList.remove('projects-list-loading');

            projectBlock.remove();

            // if project on the header ; delete it

            var menuitem = document.getElementById('header-project-'+projectId);
            if( menuitem ) {
                menuitem.remove();
            }
        };
    
        var errorHandler = function( status, exception ) {
            jserror('Impossible de supprimer ce projet', status);
        };
    
        AjaxSimple('DELETE', api.endPoint+'project/'+projectId+'/delete', successHandler, errorHandler);
    }

})(this, document);

