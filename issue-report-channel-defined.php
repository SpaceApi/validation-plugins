<?php

// check that the defined communication channels are implemented
// this could be done with JSON schema dependencies but the validator
// implementation has no support for it
if(! function_exists("validate_issue_report_channel_defined"))
{
    function validate_issue_report_channel_defined($space_api_file, &$errors, &$warnings, &$valid_versions, &$invalid_versions)
    {
        global $logger;
        $logger->logDebug("Processing the plugin 'validate_issue_report_channel_defined'");

        $obj = $space_api_file->json();

        // merge both arrays to get all the versions, prior checks might
        // have moved versions from valid to invalid and thus we would not
        // check versions that already have been marked invalid but we need
        // to check those too to assign these versions the error messages
        $versions = array_merge($valid_versions, $invalid_versions);

        // the issue_report_channels field got introduced in v0.13. we define a new array for this while removing the
        // prefix '0.' in the loop header so that we can define a ordinal order because 0.8 is less than 0.13 in the
        // specs but mathematically 0.8 greater than 0.13.
        $versions_of_interest = array();
        foreach(preg_replace("/0./", "", $versions) as $version)
            if($version >= 13)
                $versions_of_interest[] = $version;

        // iterate over all the versions where this check makes sense
        foreach($versions_of_interest as $version)
        {
            $extended_version = "0.$version";

            if(property_exists($obj, "issue_report_channels"))
            {
                foreach($obj->issue_report_channels as $index => $channel)
                {
                    if(! (property_exists($obj, "contact") && property_exists($obj->contact, $channel)) )
                    {
                        // remove the version from the valid versions array
                        $pos = array_search("0.$version", $valid_versions);
                        if($pos !== false)
                            array_splice($valid_versions, $pos, 1);

                        // add it to the invalid versions array if
                        // it's not yet present
                        if( false === array_search("0.$version", $invalid_versions))
                            $invalid_versions[] = "0.$version";

                        // get the error message array of the current iterated version
                        if(property_exists($errors, $extended_version))
                        {
                            $a = $errors->$extended_version;
                        }
                        else
                            $a = array();

                        // create the error object consisting of a message and a description
                        $err = new stdClass;
                        $err->msg = "The communication channel '$channel' isn't defined in your contact section.";
                        $err->description = "";

                        // add the new error object
                        $a[] = $err;

                        // assign the new array to the errors object
                        $errors->$extended_version = $a;
                    }
                }
            }
        }

        return true;
    }

    $space_api_validator->register("validate_issue_report_channel_defined");
}