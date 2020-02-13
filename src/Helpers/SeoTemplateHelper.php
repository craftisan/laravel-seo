<?php
/**
 * @copyright Copyright (c) 2020 Deekshant Joshi
 *
 * @author    Deekshant Joshi (deekshant.joshi@gmail.com)
 * @since     13 February 2020
 */

namespace Craftisan\Seo\Helpers;

use Craftisan\Seo\Extensions\Form;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SeoTemplateHelper
 * @package Craftisan\Seo\Helpers
 */
class SeoTemplateHelper
{

    /**
     * Extracts and returns the variables from all the input fields of a template form
     * Assuming model used in form is @see \Craftisan\Seo\Models\SeoTemplate
     *
     * @param \Craftisan\Seo\Extensions\Form $form
     *
     * @return array
     */
    public static function getVariablesFromTemplateInput(Form $form)
    {
        $seoVariables = [];
        foreach ($form->model()->getFillable() as $attribute) {
            // Extract variables from string input
            $variables = self::extractVariablesFromString($form->input($attribute));
            if ($variables instanceof Response) {
                return $variables;
            }

            $seoVariables = array_merge($seoVariables, $variables);
        }

        return array_values(array_unique($seoVariables));
    }

    /**
     * @param array|string $string
     *
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public static function extractVariablesFromString($string)
    {
        $matches = $patternMatches = [];
        // To handle an array of string passed for example in keywords field ['string0', 'string1',..]
        $string = is_array($string) ? $string : [$string];

        foreach ($string as $str) {
            // Don't allow empty string
            if (empty($str)) {
                continue;
            }

            // Pattern matching to extract variable between `{{` and `}}` throughout the string
            $match = preg_match_all('/{{(.*?)}}/', $str, $m);

            if ($match !== false || $match !== 0) {
                $patternMatches[] = $m[1];
            }
        }

        // Filter the matches before returning to check for variable nomenclature:
        // Only lowercase letters and _ are allowed
        foreach ($patternMatches as $key => $match) {
            foreach ($match as $key2 => $match2) {
                // Check if the variable is lower case barring '_'
                $charCheck = str_replace('_', '', $match2);
                if (!ctype_alnum($charCheck) && !ctype_lower(preg_replace('/[0-9]+/', '', $charCheck))) {
                    $error = new MessageBag([
                        'title' => trans('seo.admin.template_error'),
                        'message' => trans('seo.admin.variable_name_incorrect', ['string' => $string[0]]),
                    ]);

                    return back()->with(compact('error'))->withInput();
                }
            }
            $matches = array_merge($matches, array_values($match));
        }

        return array_values(array_unique($matches));
    }
}