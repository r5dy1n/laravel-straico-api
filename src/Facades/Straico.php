<?php

namespace r5dy1n\Straico\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array listModels()
 * @method static array retrieveModel(string $modelId)
 * @method static array createCompletion(array $params)
 * @method static array createChatCompletion(array $params)
 * @method static array createImage(array $params)
 * @method static array createImageEdit(array $params)
 * @method static array createImageVariation(array $params)
 * @method static array createEmbedding(array $params)
 * @method static array createTranscription(array $params)
 * @method static array createTranslation(array $params)
 * @method static array listFiles()
 * @method static array uploadFile(array $params)
 * @method static array deleteFile(string $fileId)
 * @method static array retrieveFile(string $fileId)
 * @method static string retrieveFileContent(string $fileId)
 * @method static array createFineTune(array $params)
 * @method static array listFineTunes()
 * @method static array retrieveFineTune(string $fineTuneId)
 * @method static array cancelFineTune(string $fineTuneId)
 * @method static array listFineTuneEvents(string $fineTuneId, array $queryParams = [])
 * @method static array deleteFineTuneModel(string $model)
 * @method static array createModeration(array $params)
 * @method static array listEngines()
 * @method static array retrieveEngine(string $engineId)
 *
 * @see \r5dy1n\Straico\StraicoService
 */
class Straico extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'straico'; // Matches the alias in StraicoServiceProvider
    }
}