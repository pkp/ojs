<?php

namespace APP\migration\upgrade\v3_6_0;

class I10404_UpdateCategoryImageNameFields extends \PKP\migration\upgrade\v3_6_0\I10404_UpdateCategoryImageNameFields
{
    public function getContextFolderName(): string
    {
        return 'journals';
    }
}
