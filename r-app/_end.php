<?php
// Код после работы вашего приложения
Remus()->endApp(); // Конец приложения и отправка данных пользователю!

if(REMUSPANEL){
    RemusPanel::renderPanel();
}