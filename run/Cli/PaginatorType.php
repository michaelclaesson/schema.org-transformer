<?php

namespace SchemaTransformer\Run\Cli;

enum PaginatorType: string
{
    case Wordpress = 'wordpress';
    case GetParam  = 'get-param';
}
