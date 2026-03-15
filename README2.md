# Medicina Laboral WhatsApp MVP

MVP del sistema de Medicina Laboral UNLu orientado al registro guiado de:

- aviso de ausencia
- anticipo de certificado médico

La solución actual está implementada con **Laravel + PostgreSQL + Docker** y utiliza **WhatsApp Cloud API** como canal de interacción con el usuario.

## Objetivo del proyecto

El objetivo es construir un sistema conversacional que permita registrar interacciones por WhatsApp de forma guiada, trazable y mantenible, separando claramente:

- la **conversación** con el usuario
- el **aviso de ausencia** como entidad de negocio
- el **anticipo de certificado médico** como entidad de negocio asociada a un aviso previo

## Estado actual

Hoy existe una base funcional inicial que:

- recibe mensajes desde el webhook de WhatsApp
- crea o recupera una conversación
- responde con menú y mensajes simples
- registra datos básicos de prueba

A partir de ahora el foco del proyecto pasa a estar en:

1. mejorar el **motor de conversación**
2. modelar la **trazabilidad completa**
3. implementar los flujos reales de:
   - aviso de ausencia
   - anticipo de certificado médico
4. preparar la solución para validaciones, automatismos e integraciones futuras

## Principios de diseño

- No hardcodear textos en controllers o services.
- Los textos deben vivir en `lang/es/*.php`.
- Los parámetros configurables deben vivir en `config/*.php`.
- Toda interacción debe quedar asociada a una conversación.
- No se deben borrar conversaciones ni mensajes cancelados o expirados.
- Un aviso no es una conversación: el aviso se materializa al finalizar correctamente un flujo.
- Un anticipo de certificado requiere un aviso previo válido o abierto.
- Los automatismos de inactividad se implementarán con **Laravel Scheduler**.
- Los mensajes largos o parametrizados se renderizarán con **Blade templates**.

## Estructura documental

La documentación funcional y técnica para desarrollo vive en `docs/`.

Documentos clave:

- `docs/README.md`
- `docs/05-motor-de-conversacion.md`

A futuro se agregarán documentos específicos para:

- arquitectura
- modelo de datos
- flujo de aviso de ausencia
- flujo de anticipo de certificado
- validaciones
- scheduler e inactividad
- templates de mensajes
- integraciones futuras

## Documentación para agentes de IA

Este repositorio debe incluir un archivo `AGENTS.md` en la raíz con:

- contexto del proyecto
- reglas de diseño
- documentos obligatorios a leer
- convenciones para cambios

Ese archivo funciona como guía operativa para agentes que trabajen sobre el repo.

## Stack actual

- Laravel
- PHP
- PostgreSQL
- Docker / Docker Compose
- WhatsApp Cloud API

## Puesta en marcha rápida

1. Copiar entorno:
   ```bash
   cp .env.docker.example .env