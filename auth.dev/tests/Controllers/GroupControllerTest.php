<?php

namespace PhpProjects\AuthDev\Controllers;

use Phake;
use PhpProjects\AuthDev\Model\Csrf\CsrfService;
use PhpProjects\AuthDev\Model\DuplicateEntityException;
use PhpProjects\AuthDev\Model\Group\GroupEntity;
use PhpProjects\AuthDev\Model\Group\GroupRepository;
use PhpProjects\AuthDev\Model\Group\GroupValidation;
use PhpProjects\AuthDev\Model\Permission\PermissionRepository;
use PhpProjects\AuthDev\Model\ValidationResults;
use PhpProjects\AuthDev\Views\ViewService;
use PHPUnit\Framework\TestCase;

class GroupControllerTest extends TestCase
{
    /**
     * @var GroupController
     */
    private $groupController;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @var PermissionRepository
     */
    private $permissionRepository;

    /**
     * @var \ArrayIterator
     */
    private $groupList;

    /**
     * @var \ArrayIterator
     */
    private $permissionList;

    /**
     * @var ViewService
     */
    private $viewService;

    /**
     * @var GroupValidation
     */
    private $groupValidation;

    /**
     * @var CsrfService
     */
    private $csrfService;

    protected function setUp()
    {
        $this->groupList = new \ArrayIterator([
            GroupEntity::createFromArray([ 'name' => 'taken.group01' ]),
            GroupEntity::createFromArray([ 'name' => 'taken.group02' ]),
            GroupEntity::createFromArray([ 'name' => 'taken.group03' ]),
        ]);

        $this->viewService = Phake::mock(ViewService::class);

        $this->groupRepository = Phake::mock(GroupRepository::class);
        Phake::when($this->groupRepository)->getSortedList->thenReturn($this->groupList);
        Phake::when($this->groupRepository)->getCount->thenReturn(30);
        Phake::when($this->groupRepository)->getListMatchingFriendlyName->thenReturn($this->groupList);
        Phake::when($this->groupRepository)->getCountMatchingFriendlyName->thenReturn(30);

        $this->permissionRepository = Phake::mock(PermissionRepository::class);
        $this->permissionList = new \ArrayIterator([
            GroupEntity::createFromArray(['id' => 1, 'name' => 'Permission 1']),
            GroupEntity::createFromArray(['id' => 2, 'name' => 'Permission 2']),
            GroupEntity::createFromArray(['id' => 3, 'name' => 'Permission 3']),
        ]);
        Phake::when($this->permissionRepository)->getSortedList->thenReturn($this->permissionList);

        $this->groupValidation = Phake::mock(GroupValidation::class);
        Phake::when($this->groupValidation)->validate->thenReturn(new ValidationResults([]));

        $this->csrfService = Phake::mock(CsrfService::class);
        Phake::when($this->csrfService)->validateToken->thenReturn(true);

        $this->groupController = Phake::partialMock(GroupController::class, $this->viewService, $this->groupRepository, $this->groupValidation, $this->permissionRepository, $this->csrfService);
        Phake::when($this->groupController)->checkForPermission->thenReturn(true);
    }

    public function testGetListPage1()
    {
        $this->groupController->getList(1);

        Phake::verify($this->groupRepository)->getSortedList(10, 0);
        Phake::verify($this->groupRepository)->getCount();
        Phake::verify($this->viewService)->renderView('groups/list', [
            'entities' => $this->groupList,
            'currentPage' => 1,
            'totalPages' => 3,
            'term' => '',
        ]);
    }

    public function testGetListPage2()
    {
        $this->groupController->getList(2);

        Phake::verify($this->groupRepository)->getSortedList(10, 10);
        Phake::verify($this->groupRepository)->getCount();
        Phake::verify($this->viewService)->renderView('groups/list', [
            'entities' => $this->groupList,
            'currentPage' => 2,
            'totalPages' => 3,
            'term' => '',
        ]);
    }

    public function testGetListWithSearchPage1()
    {
        $this->groupController->getList(1, 'group0');

        Phake::verify($this->groupRepository, Phake::never())->getSortedList;
        Phake::verify($this->groupRepository, Phake::never())->getCount;
        Phake::verify($this->groupRepository)->getListMatchingFriendlyName('group0', 10, 0);
        Phake::verify($this->groupRepository)->getCountMatchingFriendlyName('group0');
        Phake::verify($this->viewService)->renderView('groups/list', [
            'entities' => $this->groupList,
            'currentPage' => 1,
            'totalPages' => 3,
            'term' => 'group0',
        ]);
    }

    public function testGetListWithSearchPage2()
    {
        $this->groupController->getList(2, 'group0');

        Phake::verify($this->groupRepository, Phake::never())->getSortedList;
        Phake::verify($this->groupRepository, Phake::never())->getCount;
        Phake::verify($this->groupRepository)->getListMatchingFriendlyName('group0', 10, 10);
        Phake::verify($this->groupRepository)->getCountMatchingFriendlyName('group0');
        Phake::verify($this->viewService)->renderView('groups/list', [
            'entities' => $this->groupList,
            'currentPage' => 2,
            'totalPages' => 3,
            'term' => 'group0',
        ]);
    }

    public function testGetListChecksRedirectMessage()
    {
        Phake::when($this->viewService)->getRedirectMessage->thenReturn('My flash message');

        $this->groupController->getList();

        Phake::verify($this->viewService)->getRedirectMessage();
        Phake::verify($this->viewService)->renderView($this->anything(), Phake::capture($templateData));

        $this->assertArrayHasKey('message', $templateData);
        $this->assertEquals('My flash message', $templateData['message']);
    }

    public function testGetNew()
    {
        Phake::when($this->csrfService)->getNewToken->thenReturn('1itfuefduyp9h');

        $this->groupController->getNew();

        Phake::verify($this->viewService)->renderView('groups/form', Phake::capture($templateData));
        Phake::verify($this->csrfService)->getNewToken();

        $this->assertArrayHasKey('entity', $templateData);
        $this->assertInstanceOf(GroupEntity::class, $templateData['entity']);

        $this->assertArrayHasKey('validationResults', $templateData);
        $this->assertInstanceOf(ValidationResults::class, $templateData['validationResults']);
        $this->assertTrue($templateData['validationResults']->isValid());

        $this->assertArrayHasKey('token', $templateData);
        $this->assertEquals('1itfuefduyp9h', $templateData['token']);
    }

    public function testPostNew()
    {
        $this->groupController->postNew([
            'name' => 'Test Group',
            'token' => '123456',
        ]);

        /* @var $group GroupEntity */
        Phake::verify($this->groupValidation)->validate(Phake::capture($group));
        $this->assertEquals('Test Group', $group->getName());

        Phake::verify($this->csrfService)->validateToken('123456');
        Phake::verify($this->groupRepository)->saveEntity($group);


        Phake::verify($this->viewService)->redirect('/groups/', 303, 'Group Test Group successfully edited!');
    }

    public function testPostNewInvalid()
    {
        $validationResult = new ValidationResults(['name' => [ 'name is empty' ]]);
        Phake::when($this->groupValidation)->validate->thenReturn($validationResult);

        $this->groupController->postNew([
            'name' => '',
        ]);

        Phake::verify($this->groupRepository, Phake::never())->saveEntity;
        Phake::verify($this->viewService, Phake::never())->redirect;

        Phake::verify($this->viewService)->renderView('groups/form', Phake::capture($templateData));
        $this->assertArrayHasKey('entity', $templateData);
        $this->assertEquals('', $templateData['entity']->getName());

        $this->assertArrayHasKey('validationResults', $templateData);
        $this->assertEquals($validationResult, $templateData['validationResults']);
    }
    public function testPostNewDuplicateGroup()
    {
        Phake::when($this->groupRepository)->saveEntity->thenThrow(new DuplicateEntityException('name', new \Exception()));

        $this->groupController->postNew([
            'name' => 'Test Group',
        ]);

        Phake::verify($this->viewService, Phake::never())->redirect;

        Phake::verify($this->viewService)->renderView('groups/form', Phake::capture($templateData));

        $this->assertArrayHasKey('validationResults', $templateData);
        $this->assertEquals(['This name is already registered. Please try another.'], $templateData['validationResults']->getValidationErrorsForField('name'));
    }

    public function testPostNewMismatchedCsrfToken()
    {
        Phake::when($this->csrfService)->validateToken->thenReturn(false);

        $this->groupController->postNew([
            'name' => 'Test Group',
            'token' => '123456',
        ]);

        Phake::verify($this->viewService, Phake::never())->redirect;

        Phake::verify($this->viewService)->renderView('groups/form', Phake::capture($templateData));

        $this->assertArrayHasKey('validationResults', $templateData);
        $this->assertEquals(['Your session has expired, please try again'], $templateData['validationResults']->getValidationErrorsForField('form'));
    }

    public function testGetDetail()
    {
        Phake::when($this->csrfService)->getNewToken->thenReturn('1itfuefduyp9h');

        $group = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn($group);

        $this->groupController->getDetail('Test Group');

        Phake::verify($this->groupRepository)->getByFriendlyName('Test Group');
        Phake::verify($this->viewService)->renderView('groups/form', [
            'entity' => $group,
            'permissions' => iterator_to_array($this->permissionList),
            'validationResults' => new ValidationResults([]),
            'token' => '1itfuefduyp9h',
        ]);
    }

    public function testGetDetailNoGroup()
    {
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn(null);

        try
        {
            $this->groupController->getDetail('Test Group');
            $this->fail('A ContentNotFoundException exception should have been thrown');
        }
        catch (ContentNotFoundException $e)
        {
            Phake::verify($this->viewService, Phake::never())->renderView;
            $this->assertEquals('Group Not Found', $e->getTitle());
            $this->assertEquals('I could not locate the group Test Group.', $e->getMessage());
            $this->assertEquals('/groups/', $e->getRecommendedUrl());
            $this->assertEquals('View All Groups', $e->getRecommendedAction());
        }
    }

    public function testPostDetail()
    {
        $existingGroup = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn($existingGroup);

        $this->groupController->postDetail('Test Group', [
            'name' => 'Test Group 2',
            'token' => '123456'
        ]);

        Phake::verify($this->csrfService)->validateToken('123456');

        Phake::verify($this->groupRepository)->getByFriendlyName('Test Group');
        /* @var $group GroupEntity */
        Phake::verify($this->groupValidation)->validate($existingGroup);
        $this->assertEquals('Test Group 2', $existingGroup->getName());

        Phake::verify($this->groupRepository)->saveEntity($existingGroup);

        Phake::verify($this->viewService)->redirect('/groups/', 303, 'Group Test Group 2 successfully edited!');
    }

    public function testGetRemove()
    {
        Phake::when($this->csrfService)->getNewToken->thenReturn('1itfuefduyp9h');

        $group = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        Phake::when($this->groupRepository)->getListByFriendlyNames->thenReturn(new \ArrayIterator([$group]));

        $_SERVER['HTTP_REFERER'] = '/mytest/';

        $this->groupController->getRemove([
            'entities' => [
                'Test Group',
                'group2'
            ],
        ]);

        Phake::verify($this->groupRepository)->getListByFriendlyNames(['Test Group', 'group2']);
        Phake::verify($this->viewService)->renderView('groups/removeList', Phake::capture($templateData));

        $this->assertEquals('1itfuefduyp9h', $templateData['token']);
        $this->assertEquals([ $group ], iterator_to_array($templateData['entities']));
        $this->assertEquals('/mytest/', $templateData['originalUrl']);
    }

    public function testPostRemove()
    {
        $this->groupController->postRemove([
            'entities' => [
                'Test Group',
                'group2'
            ],
            'token' => '1itfuefduyp9h',
            'originalUrl' => '/mytest/',
        ]);

        Phake::verify($this->groupRepository)->deleteByFriendlyNames(['Test Group', 'group2']);
        Phake::verify($this->viewService)->redirect('/mytest/', 303, 'Groups successfully removed: Test Group, group2');
    }

    public function testPostRemoveInvalidToken()
    {
        Phake::when($this->csrfService)->validateToken->thenReturn(false);

        $this->groupController->postRemove([
            'entities' => [
                'Test Group',
                'group2'
            ],
            'originalUrl' => '/mytest/',
        ]);

        Phake::verify($this->groupRepository, Phake::never())->deleteByFriendlyNames;
        Phake::verify($this->viewService)->redirect('/mytest/', 303, "Your session has expired, please try deleting those groups again");
    }

    public function testUpdatePermissionsAddPermissions()
    {
        $group = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn($group);

        $this->groupController->postUpdatePermissions('Test Group', [
            'token' => '1itfuefduyp9h',
            'permissionIds' => [1, 2, 3],
            'operation' => 'add'
        ]);

        Phake::verify($this->groupRepository)->getByFriendlyName('Test Group');
        $this->assertEquals([1, 2, 3], $group->getPermissionIds());

        Phake::verify($this->groupRepository)->saveEntity($group);

        Phake::verify($this->viewService)->redirect('/groups/detail/Test+Group', 303, "Your permissions have been updated", 'success');
    }

    public function testUpdatePermissionsRemovesPermissions()
    {
        $group = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        $group->addPermissions([1, 2, 3]);
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn($group);

        $this->groupController->postUpdatePermissions('Test Group', [
            'token' => '1itfuefduyp9h',
            'permissionIds' => [2],
            'operation' => 'remove'
        ]);

        Phake::verify($this->groupRepository)->getByFriendlyName('Test Group');
        $this->assertEquals([1, 3], $group->getPermissionIds());

        Phake::verify($this->groupRepository)->saveEntity($group);

        Phake::verify($this->viewService)->redirect('/groups/detail/Test+Group', 303, "Your permissions have been updated", 'success');
    }

    public function testUpdatePermissionsInvalidToken()
    {
        Phake::when($this->csrfService)->validateToken->thenReturn(false);

        $group = GroupEntity::createFromArray([
            'name' => 'Test Group',
        ]);
        $group->addPermissions([1, 2, 3]);
        Phake::when($this->groupRepository)->getByFriendlyName->thenReturn($group);

        $this->groupController->postUpdatePermissions('Test Group', [
            'token' => '1itfuefduyp9h',
            'permissionIds' => [2],
            'operation' => 'remove'
        ]);

        Phake::verify($this->groupRepository, Phake::never())->saveEntity($group);

        Phake::verify($this->viewService)->redirect('/groups/detail/Test+Group', 303, "Your session has expired, please try updating permissions again", 'danger');
    }
}