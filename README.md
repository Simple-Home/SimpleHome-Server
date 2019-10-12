<!--
*** Smart_Home by JonatanRek
*** README by revox, 2019
-->

<!-- LOGO -->
<p align="center">
  <img src="ICON HERE" height="100" width="100">
</p>

[![PHP](https://img.shields.io/badge/PHP-brightgreen.svg)](https://github.com/GamerClassN7/Smart_Home/search?l=php)
[![JS](https://img.shields.io/badge/JS-red.svg)](https://github.com/GamerClassN7/Smart_Home/search?l=js)
[![HTML](https://img.shields.io/badge/HTML-blue.svg)](https://github.com/GamerClassN7/Smart_Home/search?l=html)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Discord](https://img.shields.io/discord/604697675430101003.svg?color=Blue&label=Discord&logo=Discord)](https://discord.gg/6BPErAS)

<!-- ABOUT THE PROJECT -->
## About Smart_Home

**Smart_Home** is a home automation project suited for **ESP boards** family (including NodeMCU). It supports PWA; supports tons of sensors; has modern and elegant UI; is easily customizable; is multilangual.

#### What works with Smart_Home?
* temperature sensors (e.g. DHT11/22),
* humidity sensors (e.g. DHT11/22),
* light detectors,
* water leak sensors,
* voltage meters,
* electricity consumption meters,
* switches,
* and many more...

<!-- TABLE OF CONTENTS -->
## Table of Contents

* [About Smart_Home](#about-smart_home)
* [Screenshots](#screenshots)
* [Installation](#instalation)
* [Contributing](#contributing)
* [License](#license)
* [Built With](#built-with)
* [Authors](#authors)

## Installation
//zrób tu dużo panie revoxie!


## Screenshots
### Browser (Desktop PWA)

<img src="./_README_IMG/1.png" height="250" width="500"> <img src="./_README_IMG/2.png" height="250" width="500">
<img src="./_README_IMG/3.png" height="250" width="500"> <img src="./_README_IMG/4.png" height="250" width="500">

### Mobile (PWA)

<img src="./_README_IMG/6.png" height="250" width="125"> <img src="./_README_IMG/7.png" height="250" width="125"> <img src="./_README_IMG/8.png" height="250" width="125"> <img src="./_README_IMG/9.png" height="250" width="125"> <img src="./_README_IMG/10.png" height="250" width="125">

## API
### POST Message (switch)
```
{
	"token":"2"
}
```

### Answer (switch)
```
{
	"device":{
		"hostname":"2",
		"sleepTime":0
		},
		"state":"succes",
		"value":"0"
	}
}
```

### POST Message (sensor)
```
{
	"token":"4",
	"values":{
		"door":{
			"value":1
		}
	}
}
```

### Answer (sensor)
```
{

}
```

## Contributions
//tu jebnę coś o pull requestach

## Authors
//tu jebnę autorów