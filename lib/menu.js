//  menu.js 
//  Copyright (C) 2016, Tom Milner (tomkmilner@gmail.com)
//  All Rights Reserved
//  June 30, 2016
//
//  Support for Side Navigation menu
//  ---

    function menu_open() {
        // No shifting of main
        // document.getElementById("main").style.marginLeft = "25%";
        // document.getElementById("menuSideNav").style.width = "25%";
        document.getElementById("menuSideNav").style.display = "block";
        document.getElementById("navIcon").style.display = 'none';
    }
    function menu_close() {
        // document.getElementById("main").style.marginLeft = "0%";
        document.getElementById("menuSideNav").style.display = "none";
        document.getElementById("navIcon").style.display = "inline-block";
    }
    function menu_go( link ) {
        menu_close();
        window.location = link;
    }
