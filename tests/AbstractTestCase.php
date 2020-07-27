<?php

namespace Valantic\DataQualityBundle\Tests;

use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaReader;
use Valantic\DataQualityBundle\Config\V1\Meta\Writer as MetaWriter;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsReader;
use Valantic\DataQualityBundle\Config\V1\Constraints\Writer as ConstraintsWriter;
use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Service\Information\FieldCollectionInformation;
use Valantic\DataQualityBundle\Service\Information\ObjectBrickInformation;

abstract class AbstractTestCase extends KernelTestCase
{
    public const CONFIG_FULL = 'config_full.yml';
    public const CONFIG_CORRUPT = 'config_corrupt.yml';
    public const CONFIG_EMPTY = 'config_empty.yml';
    public const CONFIG_STRING = 'config_string.yml';

    /**
     * @var ContainerInterface
     */
    protected static $container;

    public static function setUpBeforeClass(): void
    {
        $kernel = self::bootKernel();
        self::$container = $kernel->getContainer();
        static::cleanUp();
    }

    protected function tearDown(): void
    {
        self::cleanUp();
    }

    protected static function cleanUp(): void
    {
        array_map('unlink', array_filter((array)glob(__DIR__ . '/scratch/*') ?: []));
    }

    protected function activateConfig(string $name): void
    {
        copy(__DIR__ . '/fixtures/' . $name, __DIR__ . '/scratch/valantic_dataquality_config.yml');
    }

    protected function deleteConfig(): void
    {
        $name = __DIR__ . '/scratch/valantic_dataquality_config.yml';
        if (file_exists($name)) {
            unlink($name);
        }
    }

    protected function getProductClassDefinition(): ClassDefinition
    {
        return unserialize(base64_decode('Tzo0MDoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvbiI6Mjc6e3M6MjoiaWQiO3M6NzoicHJvZHVjdCI7czo0OiJuYW1lIjtzOjc6IlByb2R1Y3QiO3M6MTE6ImRlc2NyaXB0aW9uIjtzOjA6IiI7czoxMjoiY3JlYXRpb25EYXRlIjtpOjA7czoxNjoibW9kaWZpY2F0aW9uRGF0ZSI7aToxNTkzNjczNzcwO3M6OToidXNlck93bmVyIjtpOjI7czoxNjoidXNlck1vZGlmaWNhdGlvbiI7aToyO3M6MTE6InBhcmVudENsYXNzIjtzOjA6IiI7czoyMDoiaW1wbGVtZW50c0ludGVyZmFjZXMiO3M6MDoiIjtzOjE4OiJsaXN0aW5nUGFyZW50Q2xhc3MiO3M6MDoiIjtzOjk6InVzZVRyYWl0cyI7czowOiIiO3M6MTY6Imxpc3RpbmdVc2VUcmFpdHMiO3M6MDoiIjtzOjEzOiIAKgBlbmNyeXB0aW9uIjtiOjA7czoxODoiACoAZW5jcnlwdGVkVGFibGVzIjthOjA6e31zOjEyOiJhbGxvd0luaGVyaXQiO2I6MTtzOjEzOiJhbGxvd1ZhcmlhbnRzIjtOO3M6MTI6InNob3dWYXJpYW50cyI7YjowO3M6MTY6ImZpZWxkRGVmaW5pdGlvbnMiO2E6Njp7czoxMToiZGVzY3JpcHRpb24iO086NTQ6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxUZXh0YXJlYSI6MjM6e3M6OToiZmllbGR0eXBlIjtzOjg6InRleHRhcmVhIjtzOjU6IndpZHRoIjtzOjA6IiI7czo2OiJoZWlnaHQiO3M6MDoiIjtzOjk6Im1heExlbmd0aCI7TjtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czoyMjoiZXhjbHVkZUZyb21TZWFyY2hJbmRleCI7YjowO3M6MTU6InF1ZXJ5Q29sdW1uVHlwZSI7czo4OiJsb25ndGV4dCI7czoxMDoiY29sdW1uVHlwZSI7czo4OiJsb25ndGV4dCI7czoxMDoicGhwZG9jVHlwZSI7czo2OiJzdHJpbmciO3M6NDoibmFtZSI7czoxMToiZGVzY3JpcHRpb24iO3M6NToidGl0bGUiO3M6MTE6IkRlc2NyaXB0aW9uIjtzOjc6InRvb2x0aXAiO3M6MDoiIjtzOjk6Im1hbmRhdG9yeSI7YjowO3M6MTE6Im5vdGVkaXRhYmxlIjtiOjA7czo1OiJpbmRleCI7YjowO3M6NjoibG9ja2VkIjtiOjA7czo1OiJzdHlsZSI7czowOiIiO3M6MTE6InBlcm1pc3Npb25zIjtOO3M6ODoiZGF0YXR5cGUiO3M6NDoiZGF0YSI7czoxMjoicmVsYXRpb25UeXBlIjtiOjA7czo5OiJpbnZpc2libGUiO2I6MDtzOjE1OiJ2aXNpYmxlR3JpZFZpZXciO2I6MDtzOjEzOiJ2aXNpYmxlU2VhcmNoIjtiOjA7fXM6NDoiYmFycyI7Tzo1ODoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvblxEYXRhXE9iamVjdGJyaWNrcyI6MTk6e3M6OToiZmllbGR0eXBlIjtzOjEyOiJvYmplY3Ricmlja3MiO3M6MTA6InBocGRvY1R5cGUiO3M6Mzc6IlxQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcT2JqZWN0YnJpY2siO3M6MTI6ImFsbG93ZWRUeXBlcyI7YToxOntpOjA7czo4OiJCYXJjb2RlcyI7fXM6ODoibWF4SXRlbXMiO3M6MDoiIjtzOjY6ImJvcmRlciI7YjowO3M6NDoibmFtZSI7czo0OiJiYXJzIjtzOjU6InRpdGxlIjtzOjQ6ImJBclMiO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MDtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDt9czoxMDoiYXR0cmlidXRlcyI7Tzo2MjoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvblxEYXRhXEZpZWxkY29sbGVjdGlvbnMiOjI0OntzOjk6ImZpZWxkdHlwZSI7czoxNjoiZmllbGRjb2xsZWN0aW9ucyI7czoxMDoicGhwZG9jVHlwZSI7czo0MToiXFBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxGaWVsZGNvbGxlY3Rpb24iO3M6MTI6ImFsbG93ZWRUeXBlcyI7YToxOntpOjA7czoxMDoiQXR0cmlidXRlcyI7fXM6MTE6ImxhenlMb2FkaW5nIjtiOjE7czo4OiJtYXhJdGVtcyI7czowOiIiO3M6MTc6ImRpc2FsbG93QWRkUmVtb3ZlIjtiOjA7czoxNToiZGlzYWxsb3dSZW9yZGVyIjtiOjA7czo5OiJjb2xsYXBzZWQiO2I6MDtzOjExOiJjb2xsYXBzaWJsZSI7YjowO3M6NjoiYm9yZGVyIjtiOjA7czo0OiJuYW1lIjtzOjEwOiJhdHRyaWJ1dGVzIjtzOjU6InRpdGxlIjtzOjEwOiJhdHRyaUJVVGVzIjtzOjc6InRvb2x0aXAiO3M6MDoiIjtzOjk6Im1hbmRhdG9yeSI7YjowO3M6MTE6Im5vdGVkaXRhYmxlIjtiOjA7czo1OiJpbmRleCI7YjowO3M6NjoibG9ja2VkIjtiOjA7czo1OiJzdHlsZSI7czowOiIiO3M6MTE6InBlcm1pc3Npb25zIjtOO3M6ODoiZGF0YXR5cGUiO3M6NDoiZGF0YSI7czoxMjoicmVsYXRpb25UeXBlIjtiOjA7czo5OiJpbnZpc2libGUiO2I6MDtzOjE1OiJ2aXNpYmxlR3JpZFZpZXciO2I6MDtzOjEzOiJ2aXNpYmxlU2VhcmNoIjtiOjA7fXM6MTU6ImxvY2FsaXplZGZpZWxkcyI7Tzo2MToiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvblxEYXRhXExvY2FsaXplZGZpZWxkcyI6Mjc6e3M6OToiZmllbGR0eXBlIjtzOjE1OiJsb2NhbGl6ZWRmaWVsZHMiO3M6MTA6InBocGRvY1R5cGUiO3M6NDA6IlxQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcTG9jYWxpemVkZmllbGQiO3M6NjoiY2hpbGRzIjthOjI6e2k6MDtPOjUxOiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXERhdGFcSW5wdXQiOjI1OntzOjk6ImZpZWxkdHlwZSI7czo1OiJpbnB1dCI7czo1OiJ3aWR0aCI7TjtzOjEyOiJkZWZhdWx0VmFsdWUiO047czoxNToicXVlcnlDb2x1bW5UeXBlIjtzOjc6InZhcmNoYXIiO3M6MTA6ImNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMjoiY29sdW1uTGVuZ3RoIjtpOjE5MDtzOjEwOiJwaHBkb2NUeXBlIjtzOjY6InN0cmluZyI7czo1OiJyZWdleCI7czowOiIiO3M6NjoidW5pcXVlIjtiOjA7czoxMzoic2hvd0NoYXJDb3VudCI7YjowO3M6NDoibmFtZSI7czo0OiJuYW1lIjtzOjU6InRpdGxlIjtzOjQ6Ik5hbWUiO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MDtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDtzOjIxOiJkZWZhdWx0VmFsdWVHZW5lcmF0b3IiO3M6MDoiIjt9aToxO086NTE6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxJbnB1dCI6MjU6e3M6OToiZmllbGR0eXBlIjtzOjU6ImlucHV0IjtzOjU6IndpZHRoIjtOO3M6MTI6ImRlZmF1bHRWYWx1ZSI7TjtzOjE1OiJxdWVyeUNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiY29sdW1uVHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEyOiJjb2x1bW5MZW5ndGgiO2k6MTkwO3M6MTA6InBocGRvY1R5cGUiO3M6Njoic3RyaW5nIjtzOjU6InJlZ2V4IjtzOjA6IiI7czo2OiJ1bmlxdWUiO2I6MDtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czo0OiJuYW1lIjtzOjY6InRlYXNlciI7czo1OiJ0aXRsZSI7czo2OiJUZWFzZXIiO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MDtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDtzOjIxOiJkZWZhdWx0VmFsdWVHZW5lcmF0b3IiO3M6MDoiIjt9fXM6NDoibmFtZSI7czoxNToibG9jYWxpemVkZmllbGRzIjtzOjY6InJlZ2lvbiI7TjtzOjY6ImxheW91dCI7TjtzOjU6InRpdGxlIjtzOjA6IiI7czo1OiJ3aWR0aCI7czowOiIiO3M6NjoiaGVpZ2h0IjtzOjA6IiI7czo3OiJtYXhUYWJzIjtOO3M6MTA6ImxhYmVsV2lkdGgiO047czo2OiJib3JkZXIiO2I6MDtzOjE2OiJwcm92aWRlU3BsaXRWaWV3IjtiOjA7czoxMToidGFiUG9zaXRpb24iO047czoyNToiaGlkZUxhYmVsc1doZW5UYWJzUmVhY2hlZCI7TjtzOjc6InRvb2x0aXAiO3M6MDoiIjtzOjk6Im1hbmRhdG9yeSI7YjowO3M6MTE6Im5vdGVkaXRhYmxlIjtiOjA7czo1OiJpbmRleCI7TjtzOjY6ImxvY2tlZCI7YjowO3M6NToic3R5bGUiO3M6MDoiIjtzOjExOiJwZXJtaXNzaW9ucyI7TjtzOjg6ImRhdGF0eXBlIjtzOjQ6ImRhdGEiO3M6MTI6InJlbGF0aW9uVHlwZSI7YjowO3M6OToiaW52aXNpYmxlIjtiOjA7czoxNToidmlzaWJsZUdyaWRWaWV3IjtiOjE7czoxMzoidmlzaWJsZVNlYXJjaCI7YjoxO31zOjE5OiJjbGFzc2lmaWNhdGlvblN0b3JlIjtPOjY1OiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXERhdGFcQ2xhc3NpZmljYXRpb25zdG9yZSI6MzA6e3M6OToiZmllbGR0eXBlIjtzOjE5OiJjbGFzc2lmaWNhdGlvbnN0b3JlIjtzOjEwOiJwaHBkb2NUeXBlIjtzOjQ1OiJcUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzaWZpY2F0aW9uc3RvcmUiO3M6NjoiY2hpbGRzIjthOjA6e31zOjQ6Im5hbWUiO3M6MTk6ImNsYXNzaWZpY2F0aW9uU3RvcmUiO3M6NjoicmVnaW9uIjtOO3M6NjoibGF5b3V0IjtOO3M6NToidGl0bGUiO3M6MTk6ImNsYXNzaWZpY2F0aW9uU3RvcmUiO3M6NToid2lkdGgiO3M6MDoiIjtzOjY6ImhlaWdodCI7czowOiIiO3M6NzoibWF4VGFicyI7TjtzOjEwOiJsYWJlbFdpZHRoIjtpOjA7czo5OiJsb2NhbGl6ZWQiO2I6MDtzOjc6InN0b3JlSWQiO3M6MToiMSI7czoxMzoiaGlkZUVtcHR5RGF0YSI7YjowO3M6MTc6ImRpc2FsbG93QWRkUmVtb3ZlIjtiOjA7czoxNToiYWxsb3dlZEdyb3VwSWRzIjthOjA6e31zOjIyOiJhY3RpdmVHcm91cERlZmluaXRpb25zIjthOjA6e31zOjg6Im1heEl0ZW1zIjtpOjA7czo3OiJ0b29sdGlwIjtzOjA6IiI7czo5OiJtYW5kYXRvcnkiO2I6MDtzOjExOiJub3RlZGl0YWJsZSI7YjowO3M6NToiaW5kZXgiO2I6MDtzOjY6ImxvY2tlZCI7YjowO3M6NToic3R5bGUiO3M6MDoiIjtzOjExOiJwZXJtaXNzaW9ucyI7TjtzOjg6ImRhdGF0eXBlIjtzOjQ6ImRhdGEiO3M6MTI6InJlbGF0aW9uVHlwZSI7YjowO3M6OToiaW52aXNpYmxlIjtiOjA7czoxNToidmlzaWJsZUdyaWRWaWV3IjtiOjA7czoxMzoidmlzaWJsZVNlYXJjaCI7YjowO31zOjEwOiJjYXRlZ29yaWVzIjtPOjcwOiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXERhdGFcTWFueVRvTWFueU9iamVjdFJlbGF0aW9uIjoyNjp7czo5OiJmaWVsZHR5cGUiO3M6MjQ6Im1hbnlUb01hbnlPYmplY3RSZWxhdGlvbiI7czo1OiJ3aWR0aCI7czowOiIiO3M6NjoiaGVpZ2h0IjtzOjA6IiI7czo4OiJtYXhJdGVtcyI7czowOiIiO3M6MTU6InF1ZXJ5Q29sdW1uVHlwZSI7czo0OiJ0ZXh0IjtzOjEwOiJwaHBkb2NUeXBlIjtzOjU6ImFycmF5IjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MTtzOjEzOiJ2aXNpYmxlRmllbGRzIjtzOjc6Im5hbWUsaWQiO3M6MjI6ImFsbG93VG9DcmVhdGVOZXdPYmplY3QiO2I6MDtzOjIxOiJvcHRpbWl6ZWRBZG1pbkxvYWRpbmciO2I6MDtzOjIzOiJ2aXNpYmxlRmllbGREZWZpbml0aW9ucyI7YTowOnt9czo3OiJjbGFzc2VzIjthOjE6e2k6MDthOjE6e3M6NzoiY2xhc3NlcyI7czo4OiJDYXRlZ29yeSI7fX1zOjE4OiJwYXRoRm9ybWF0dGVyQ2xhc3MiO3M6MDoiIjtzOjQ6Im5hbWUiO3M6MTA6ImNhdGVnb3JpZXMiO3M6NToidGl0bGUiO3M6MTA6IkNhdGVnb3JpZXMiO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDt9fXM6MTc6ImxheW91dERlZmluaXRpb25zIjtPOjUzOiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXExheW91dFxQYW5lbCI6MTg6e3M6OToiZmllbGR0eXBlIjtzOjU6InBhbmVsIjtzOjEwOiJsYWJlbFdpZHRoIjtpOjEwMDtzOjY6ImxheW91dCI7TjtzOjY6ImJvcmRlciI7YjowO3M6NDoibmFtZSI7czoxMjoicGltY29yZV9yb290IjtzOjQ6InR5cGUiO047czo2OiJyZWdpb24iO047czo1OiJ0aXRsZSI7TjtzOjU6IndpZHRoIjtOO3M6NjoiaGVpZ2h0IjtOO3M6MTE6ImNvbGxhcHNpYmxlIjtiOjA7czo5OiJjb2xsYXBzZWQiO2I6MDtzOjk6ImJvZHlTdHlsZSI7TjtzOjg6ImRhdGF0eXBlIjtzOjY6ImxheW91dCI7czoxMToicGVybWlzc2lvbnMiO047czo2OiJjaGlsZHMiO2E6MTp7aTowO086NTM6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cTGF5b3V0XFBhbmVsIjoxODp7czo5OiJmaWVsZHR5cGUiO3M6NToicGFuZWwiO3M6MTA6ImxhYmVsV2lkdGgiO2k6MTAwO3M6NjoibGF5b3V0IjtOO3M6NjoiYm9yZGVyIjtiOjA7czo0OiJuYW1lIjtzOjY6IkxheW91dCI7czo0OiJ0eXBlIjtOO3M6NjoicmVnaW9uIjtOO3M6NToidGl0bGUiO3M6MDoiIjtzOjU6IndpZHRoIjtOO3M6NjoiaGVpZ2h0IjtOO3M6MTE6ImNvbGxhcHNpYmxlIjtiOjA7czo5OiJjb2xsYXBzZWQiO2I6MDtzOjk6ImJvZHlTdHlsZSI7czowOiIiO3M6ODoiZGF0YXR5cGUiO3M6NjoibGF5b3V0IjtzOjExOiJwZXJtaXNzaW9ucyI7TjtzOjY6ImNoaWxkcyI7YTo2OntpOjA7cjoyMDtpOjE7cjo0NDtpOjI7cjo2NTtpOjM7cjo5MTtpOjQ7cjoxNzE7aTo1O3I6MjAyO31zOjY6ImxvY2tlZCI7YjowO3M6NDoiaWNvbiI7czowOiIiO319czo2OiJsb2NrZWQiO2I6MDtzOjQ6Imljb24iO047fXM6NDoiaWNvbiI7czowOiIiO3M6MTA6InByZXZpZXdVcmwiO3M6MDoiIjtzOjU6Imdyb3VwIjtzOjA6IiI7czoxNjoic2hvd0FwcExvZ2dlclRhYiI7YjowO3M6MjI6ImxpbmtHZW5lcmF0b3JSZWZlcmVuY2UiO3M6MDoiIjtzOjE2OiJjb21wb3NpdGVJbmRpY2VzIjthOjA6e31zOjE4OiJwcm9wZXJ0eVZpc2liaWxpdHkiO2E6Mjp7czo0OiJncmlkIjthOjY6e3M6MjoiaWQiO2I6MTtzOjM6ImtleSI7YjowO3M6NDoicGF0aCI7YjoxO3M6OToicHVibGlzaGVkIjtiOjE7czoxNjoibW9kaWZpY2F0aW9uRGF0ZSI7YjoxO3M6MTI6ImNyZWF0aW9uRGF0ZSI7YjoxO31zOjY6InNlYXJjaCI7YTo2OntzOjI6ImlkIjtiOjE7czozOiJrZXkiO2I6MDtzOjQ6InBhdGgiO2I6MTtzOjk6InB1Ymxpc2hlZCI7YjoxO3M6MTY6Im1vZGlmaWNhdGlvbkRhdGUiO2I6MTtzOjEyOiJjcmVhdGlvbkRhdGUiO2I6MTt9fXM6MTc6ImVuYWJsZUdyaWRMb2NraW5nIjtiOjA7fQ=='));
    }

    protected function getBarcodeObjectbrickDefinition(): ObjectbrickDefinition
    {
        return unserialize(base64_decode('Tzo0NzoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XE9iamVjdGJyaWNrXERlZmluaXRpb24iOjg6e3M6MTY6ImNsYXNzRGVmaW5pdGlvbnMiO2E6MTp7aTowO2E6Mjp7czo5OiJjbGFzc25hbWUiO3M6NzoiUHJvZHVjdCI7czo5OiJmaWVsZG5hbWUiO3M6NDoiYmFycyI7fX1zOjM6ImtleSI7czo4OiJCYXJjb2RlcyI7czoxMToicGFyZW50Q2xhc3MiO3M6MDoiIjtzOjIwOiJpbXBsZW1lbnRzSW50ZXJmYWNlcyI7czowOiIiO3M6NToidGl0bGUiO3M6ODoiQmFyQ29kZVMiO3M6NToiZ3JvdXAiO3M6MDoiIjtzOjE3OiJsYXlvdXREZWZpbml0aW9ucyI7Tzo1MzoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvblxMYXlvdXRcUGFuZWwiOjE4OntzOjk6ImZpZWxkdHlwZSI7czo1OiJwYW5lbCI7czoxMDoibGFiZWxXaWR0aCI7aToxMDA7czo2OiJsYXlvdXQiO047czo2OiJib3JkZXIiO2I6MDtzOjQ6Im5hbWUiO047czo0OiJ0eXBlIjtOO3M6NjoicmVnaW9uIjtOO3M6NToidGl0bGUiO047czo1OiJ3aWR0aCI7TjtzOjY6ImhlaWdodCI7TjtzOjExOiJjb2xsYXBzaWJsZSI7YjowO3M6OToiY29sbGFwc2VkIjtiOjA7czo5OiJib2R5U3R5bGUiO047czo4OiJkYXRhdHlwZSI7czo2OiJsYXlvdXQiO3M6MTE6InBlcm1pc3Npb25zIjtOO3M6NjoiY2hpbGRzIjthOjE6e2k6MDtPOjUzOiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXExheW91dFxQYW5lbCI6MTg6e3M6OToiZmllbGR0eXBlIjtzOjU6InBhbmVsIjtzOjEwOiJsYWJlbFdpZHRoIjtpOjEwMDtzOjY6ImxheW91dCI7TjtzOjY6ImJvcmRlciI7YjowO3M6NDoibmFtZSI7czo2OiJMYXlvdXQiO3M6NDoidHlwZSI7TjtzOjY6InJlZ2lvbiI7TjtzOjU6InRpdGxlIjtzOjA6IiI7czo1OiJ3aWR0aCI7TjtzOjY6ImhlaWdodCI7TjtzOjExOiJjb2xsYXBzaWJsZSI7YjowO3M6OToiY29sbGFwc2VkIjtiOjA7czo5OiJib2R5U3R5bGUiO3M6MDoiIjtzOjg6ImRhdGF0eXBlIjtzOjY6ImxheW91dCI7czoxMToicGVybWlzc2lvbnMiO047czo2OiJjaGlsZHMiO2E6Mjp7aTowO086NTE6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxJbnB1dCI6MjU6e3M6OToiZmllbGR0eXBlIjtzOjU6ImlucHV0IjtzOjU6IndpZHRoIjtOO3M6MTI6ImRlZmF1bHRWYWx1ZSI7TjtzOjE1OiJxdWVyeUNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiY29sdW1uVHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEyOiJjb2x1bW5MZW5ndGgiO2k6MTkwO3M6MTA6InBocGRvY1R5cGUiO3M6Njoic3RyaW5nIjtzOjU6InJlZ2V4IjtzOjA6IiI7czo2OiJ1bmlxdWUiO2I6MDtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czo0OiJuYW1lIjtzOjM6ImVhbiI7czo1OiJ0aXRsZSI7czozOiJFQU4iO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MDtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDtzOjIxOiJkZWZhdWx0VmFsdWVHZW5lcmF0b3IiO3M6MDoiIjt9aToxO086NTE6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxJbnB1dCI6MjU6e3M6OToiZmllbGR0eXBlIjtzOjU6ImlucHV0IjtzOjU6IndpZHRoIjtOO3M6MTI6ImRlZmF1bHRWYWx1ZSI7TjtzOjE1OiJxdWVyeUNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiY29sdW1uVHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEyOiJjb2x1bW5MZW5ndGgiO2k6MTkwO3M6MTA6InBocGRvY1R5cGUiO3M6Njoic3RyaW5nIjtzOjU6InJlZ2V4IjtzOjA6IiI7czo2OiJ1bmlxdWUiO2I6MDtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czo0OiJuYW1lIjtzOjQ6Imd0aW4iO3M6NToidGl0bGUiO3M6NDoiR1RJTiI7czo3OiJ0b29sdGlwIjtzOjA6IiI7czo5OiJtYW5kYXRvcnkiO2I6MDtzOjExOiJub3RlZGl0YWJsZSI7YjowO3M6NToiaW5kZXgiO2I6MDtzOjY6ImxvY2tlZCI7YjowO3M6NToic3R5bGUiO3M6MDoiIjtzOjExOiJwZXJtaXNzaW9ucyI7TjtzOjg6ImRhdGF0eXBlIjtzOjQ6ImRhdGEiO3M6MTI6InJlbGF0aW9uVHlwZSI7YjowO3M6OToiaW52aXNpYmxlIjtiOjA7czoxNToidmlzaWJsZUdyaWRWaWV3IjtiOjA7czoxMzoidmlzaWJsZVNlYXJjaCI7YjowO3M6MjE6ImRlZmF1bHRWYWx1ZUdlbmVyYXRvciI7czowOiIiO319czo2OiJsb2NrZWQiO2I6MDtzOjQ6Imljb24iO3M6MDoiIjt9fXM6NjoibG9ja2VkIjtiOjA7czo0OiJpY29uIjtOO31zOjE5OiIAKgBmaWVsZERlZmluaXRpb25zIjthOjI6e3M6MzoiZWFuIjtyOjQ1O3M6NDoiZ3RpbiI7cjo3MTt9fQ=='));
    }

    protected function getAttributeFieldcollectionDefinition(): FieldcollectionDefinition
    {
        return unserialize(base64_decode('Tzo1MToiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XEZpZWxkY29sbGVjdGlvblxEZWZpbml0aW9uIjo3OntzOjM6ImtleSI7czoxMDoiQXR0cmlidXRlcyI7czoxMToicGFyZW50Q2xhc3MiO3M6MDoiIjtzOjIwOiJpbXBsZW1lbnRzSW50ZXJmYWNlcyI7czowOiIiO3M6NToidGl0bGUiO3M6NToiQXR0cnMiO3M6NToiZ3JvdXAiO3M6MDoiIjtzOjE3OiJsYXlvdXREZWZpbml0aW9ucyI7Tzo1MzoiUGltY29yZVxNb2RlbFxEYXRhT2JqZWN0XENsYXNzRGVmaW5pdGlvblxMYXlvdXRcUGFuZWwiOjE4OntzOjk6ImZpZWxkdHlwZSI7czo1OiJwYW5lbCI7czoxMDoibGFiZWxXaWR0aCI7aToxMDA7czo2OiJsYXlvdXQiO047czo2OiJib3JkZXIiO2I6MDtzOjQ6Im5hbWUiO047czo0OiJ0eXBlIjtOO3M6NjoicmVnaW9uIjtOO3M6NToidGl0bGUiO047czo1OiJ3aWR0aCI7TjtzOjY6ImhlaWdodCI7TjtzOjExOiJjb2xsYXBzaWJsZSI7YjowO3M6OToiY29sbGFwc2VkIjtiOjA7czo5OiJib2R5U3R5bGUiO047czo4OiJkYXRhdHlwZSI7czo2OiJsYXlvdXQiO3M6MTE6InBlcm1pc3Npb25zIjtOO3M6NjoiY2hpbGRzIjthOjE6e2k6MDtPOjUzOiJQaW1jb3JlXE1vZGVsXERhdGFPYmplY3RcQ2xhc3NEZWZpbml0aW9uXExheW91dFxQYW5lbCI6MTg6e3M6OToiZmllbGR0eXBlIjtzOjU6InBhbmVsIjtzOjEwOiJsYWJlbFdpZHRoIjtpOjEwMDtzOjY6ImxheW91dCI7TjtzOjY6ImJvcmRlciI7YjowO3M6NDoibmFtZSI7czo2OiJMYXlvdXQiO3M6NDoidHlwZSI7TjtzOjY6InJlZ2lvbiI7TjtzOjU6InRpdGxlIjtzOjA6IiI7czo1OiJ3aWR0aCI7TjtzOjY6ImhlaWdodCI7TjtzOjExOiJjb2xsYXBzaWJsZSI7YjowO3M6OToiY29sbGFwc2VkIjtiOjA7czo5OiJib2R5U3R5bGUiO3M6MDoiIjtzOjg6ImRhdGF0eXBlIjtzOjY6ImxheW91dCI7czoxMToicGVybWlzc2lvbnMiO047czo2OiJjaGlsZHMiO2E6Mjp7aTowO086NTE6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxJbnB1dCI6MjU6e3M6OToiZmllbGR0eXBlIjtzOjU6ImlucHV0IjtzOjU6IndpZHRoIjtOO3M6MTI6ImRlZmF1bHRWYWx1ZSI7TjtzOjE1OiJxdWVyeUNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiY29sdW1uVHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEyOiJjb2x1bW5MZW5ndGgiO2k6MTkwO3M6MTA6InBocGRvY1R5cGUiO3M6Njoic3RyaW5nIjtzOjU6InJlZ2V4IjtzOjA6IiI7czo2OiJ1bmlxdWUiO2I6MDtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czo0OiJuYW1lIjtzOjEzOiJhdHRyaWJ1dGVfa2V5IjtzOjU6InRpdGxlIjtzOjQ6ImF0X2siO3M6NzoidG9vbHRpcCI7czowOiIiO3M6OToibWFuZGF0b3J5IjtiOjA7czoxMToibm90ZWRpdGFibGUiO2I6MDtzOjU6ImluZGV4IjtiOjA7czo2OiJsb2NrZWQiO2I6MDtzOjU6InN0eWxlIjtzOjA6IiI7czoxMToicGVybWlzc2lvbnMiO047czo4OiJkYXRhdHlwZSI7czo0OiJkYXRhIjtzOjEyOiJyZWxhdGlvblR5cGUiO2I6MDtzOjk6ImludmlzaWJsZSI7YjowO3M6MTU6InZpc2libGVHcmlkVmlldyI7YjowO3M6MTM6InZpc2libGVTZWFyY2giO2I6MDtzOjIxOiJkZWZhdWx0VmFsdWVHZW5lcmF0b3IiO3M6MDoiIjt9aToxO086NTE6IlBpbWNvcmVcTW9kZWxcRGF0YU9iamVjdFxDbGFzc0RlZmluaXRpb25cRGF0YVxJbnB1dCI6MjU6e3M6OToiZmllbGR0eXBlIjtzOjU6ImlucHV0IjtzOjU6IndpZHRoIjtOO3M6MTI6ImRlZmF1bHRWYWx1ZSI7TjtzOjE1OiJxdWVyeUNvbHVtblR5cGUiO3M6NzoidmFyY2hhciI7czoxMDoiY29sdW1uVHlwZSI7czo3OiJ2YXJjaGFyIjtzOjEyOiJjb2x1bW5MZW5ndGgiO2k6MTkwO3M6MTA6InBocGRvY1R5cGUiO3M6Njoic3RyaW5nIjtzOjU6InJlZ2V4IjtzOjA6IiI7czo2OiJ1bmlxdWUiO2I6MDtzOjEzOiJzaG93Q2hhckNvdW50IjtiOjA7czo0OiJuYW1lIjtzOjE1OiJhdHRyaWJ1dGVfdmFsdWUiO3M6NToidGl0bGUiO3M6MTU6ImF0dHJpYnV0ZV92YWx1ZSI7czo3OiJ0b29sdGlwIjtzOjA6IiI7czo5OiJtYW5kYXRvcnkiO2I6MDtzOjExOiJub3RlZGl0YWJsZSI7YjowO3M6NToiaW5kZXgiO2I6MDtzOjY6ImxvY2tlZCI7YjowO3M6NToic3R5bGUiO3M6MDoiIjtzOjExOiJwZXJtaXNzaW9ucyI7TjtzOjg6ImRhdGF0eXBlIjtzOjQ6ImRhdGEiO3M6MTI6InJlbGF0aW9uVHlwZSI7YjowO3M6OToiaW52aXNpYmxlIjtiOjA7czoxNToidmlzaWJsZUdyaWRWaWV3IjtiOjA7czoxMzoidmlzaWJsZVNlYXJjaCI7YjowO3M6MjE6ImRlZmF1bHRWYWx1ZUdlbmVyYXRvciI7czowOiIiO319czo2OiJsb2NrZWQiO2I6MDtzOjQ6Imljb24iO3M6MDoiIjt9fXM6NjoibG9ja2VkIjtiOjA7czo0OiJpY29uIjtOO31zOjE5OiIAKgBmaWVsZERlZmluaXRpb25zIjthOjI6e3M6MTM6ImF0dHJpYnV0ZV9rZXkiO3I6NDE7czoxNToiYXR0cmlidXRlX3ZhbHVlIjtyOjY3O319'));
    }

    protected function getMetaReader(): MetaReader
    {
        return new MetaReader($this->createMock(EventDispatcher::class), $this->getInformationFactory());
    }

    protected function getMetaWriter(): MetaWriter
    {
        return new MetaWriter($this->getMetaReader(), $this->createMock(EventDispatcher::class));
    }


    protected function getConstraintsReader(): ConstraintsReader
    {
        return new ConstraintsReader($this->createMock(EventDispatcher::class), $this->getInformationFactory());
    }

    protected function getConstraintsWriter(): ConstraintsWriter
    {
        return new ConstraintsWriter($this->getConstraintsReader(), $this->createMock(EventDispatcher::class));
    }

    protected function getInformationFactory(): DefinitionInformationFactory
    {
        $classInformationStub = $this->getMockBuilder(ClassInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $classInformationStub->method('getDefinition')
            ->willReturn($this->getProductClassDefinition());

        $fieldCollectionInformationStub = $this->getMockBuilder(FieldCollectionInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $fieldCollectionInformationStub
            ->method('getDefinition')
            ->willReturn($this->getAttributeFieldcollectionDefinition());

        $objectBrickInformationStub = $this->getMockBuilder(ObjectBrickInformation::class)
            ->onlyMethods(['getDefinition'])
            ->getMock();
        $objectBrickInformationStub
            ->method('getDefinition')
            ->willReturn($this->getBarcodeObjectbrickDefinition());

        $definitionInformationFactory = new DefinitionInformationFactory($classInformationStub, $fieldCollectionInformationStub, $objectBrickInformationStub);

        return $definitionInformationFactory;
    }
}
