# Unit Testing Plan

This document lists all classes and methods with business logic to be covered by unit tests.
Controllers are excluded — they are covered by integration tests (see `testing_plan.md`).

**Total: 195 classes, 783 methods to cover**

## Priority Guide

| Priority    | Categories                                                   | Rationale                                       |
|-------------|--------------------------------------------------------------|-------------------------------------------------|
| 1. Critical | Managers, Entities, Tools, Services                          | Core business logic, most testable in isolation |
| 2. High     | Security (Voters, Authenticators), Communication, Validators | Authorization & message sending correctness     |
| 3. Medium   | Repositories, Twig extensions, Event subscribers, Providers  | Query correctness, display logic, integrations  |
| 4. Lower    | Commands, Forms, Models, Components, Enums, Tasks            | Infrastructure, less likely to break on upgrade |

---

## Manager (140 methods)

### App\Manager\AnswerManager

<!-- src/Manager/AnswerManager.php -->

- [X] `handleSpecialAnswers(Message $message, string $body)`
- [X] `sendSms(Message $message, string $content)`

### App\Manager\AudienceManager

<!-- src/Manager/AudienceManager.php -->

- [X] `getVolunteerList(array $ids)`
- [X] `getBadgeList(array $ids)`
- [X] `classifyAudience(array $data)`
- [X] `extractAudience(array $data)`
- [X] `extractStructures(array $data)`
- [X] `extractBadgeCounts(array $data, array $badgeList)`

### App\Manager\BadgeManager

<!-- src/Manager/BadgeManager.php -->

- [X] `getVolunteerCountInSearch(Pagerfanta $pager)`
- [X] `getPublicBadges()`
- [X] `getCustomOrPublicBadges()`
- [X] `searchForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria)`

### App\Manager\CampaignManager

<!-- src/Manager/CampaignManager.php -->

- [X] `launchNewCampaign(CampaignModel $campaignModel, ProcessorInterface $processor, Volunteer $volunteer)`
- [X] `postponeExpiration(CampaignEntity $campaign)`
- [X] `canReopenCampaign(CampaignEntity $campaign)` — A campaign can only be reopened if any of the prefix associated to
  everyone's
- [X] `getHash(int $campaignId)`

### App\Manager\CommunicationManager

<!-- src/Manager/CommunicationManager.php -->

- [X] `createNewCommunication(Campaign $campaign, BaseTrigger $trigger, Communication $communication)`
- [X] `launchNewCommunication(Campaign $campaign, Communication $communication, ?ProcessorInterface $processor)`
- [X] `createCommunicationEntityFromTrigger(BaseTrigger $trigger)`
- [X] `findCommunicationIdsRequiringReports()`
- [X] `sortAudienceByTriggeringPriority(array $mixedVolunteers)`

### App\Manager\CostManager

<!-- src/Manager/CostManager.php -->

- [X] `saveMessageCost(TwilioMessage $twilioMessage, Message $message)`
- [X] `saveCallCost(TwilioCall $twilioCall, Message $message)`
- [X] `recoverCosts()`
- [X] 
  `saveCost(string $direction, string $fromNumber, string $toNumber, string $body, string $price, string $currency, Message $message)`
- [X] `recoverMessageCosts()`
- [X] `recoverCallCosts()`

### App\Manager\DeletedVolunteerManager

<!-- src/Manager/DeletedVolunteerManager.php -->

- [X] `isDeleted(string $externalId)`
- [X] `undelete(string $externalId)`
- [X] `anonymize(Volunteer $volunteer)`

### App\Manager\ExpirableManager

<!-- src/Manager/ExpirableManager.php -->

- [X] `get(string $uuid)`
- [X] `set($data, \DateTime $expiresAt)`

### App\Manager\GdprManager

<!-- src/Manager/GdprManager.php -->

- [X] `anonymize(Volunteer $volunteer)`

### App\Manager\LanguageConfigManager

<!-- src/Manager/LanguageConfigManager.php -->

- [X] `getAvailableLanguages()`
- [X] `getAvailableLanguageChoices()`
- [X] `getLanguageConfig(string $lang)`
- [X] `createLanguageObject(array $row)`

### App\Manager\LocaleManager

<!-- src/Manager/LocaleManager.php -->

- [X] `save(string $locale)`
- [X] `restoreFromSession()`
- [X] `restoreFromUser()`
- [X] `changeLocale(string $locale)`
- [X] `getUser()`
- [X] `sanitizeLocale(string $locale)`

### App\Manager\MailManager

<!-- src/Manager/MailManager.php -->

- [X] `simple(string $to, string $subject, string $textBody, string $htmlBody, string $locale)`

### App\Manager\MaintenanceManager

<!-- src/Manager/MaintenanceManager.php -->

- [X] `refresh()`

### App\Manager\MediaManager

<!-- src/Manager/MediaManager.php -->

- [X] `createMedia(string $extension, string $text)`
- [X] `createMp3(TextToSpeechConfig $config, string $text, bool $male)`
- [X] `getMedia(string $extension, string $text, callable $callback)`
- [X] `findOneByText(string $text)`

### App\Manager\MessageManager

<!-- src/Manager/MessageManager.php -->

- [X] `generateCodes(int $numberOfCodes)`
- [X] `generatePrefixes(array $volunteers)`
- [X] `handleAnswer(string $phoneNumber, string $body)`
- [X] `getMessageFromPhoneNumber(string $phoneNumber, ?string $body)`
- [X] `addAnswer(Message $message, string $body, bool $byAdmin)`
- [X] `toggleAnswer(Message $message, Choice $choice)`
- [X] `getDeployGreenlight()` — Returns true whether is it possible to deploy, if
- [X] `getLatestMessagesForVolunteer(Volunteer $volunteer)`

### App\Manager\NivolManager

<!-- src/Manager/NivolManager.php -->

- [X] `getUserByNivol(string $nivol)`
- [X] `sendEmail(string $nivol)`
- [X] `createDigits()`

### App\Manager\OperationManager

<!-- src/Manager/OperationManager.php -->

- [X] `createOperation(CampaignModel $campaignModel, CampaignEntity $campaignEntity)`
- [X] `canBindOperation(Volunteer $volunteer, CampaignModel $campaignModel)`
- [X] `bindOperation(CampaignModel $campaignModel, CampaignEntity $campaignEntity)`
- [X] `listOperations(Structure $structure)`
- [X] `addResourceToOperation(Message $message)`
- [X] `removeResourceFromOperation(Message $message)`
- [X] `addChoicesToOperation(Communication $communication, BaseTrigger $trigger)`
- [X] `saveCampaignOperation(CampaignEntity $campaignEntity, int $id)`

### App\Manager\PegassManager

<!-- src/Manager/PegassManager.php -->

- [X] `updateEntity(Pegass $entity, array $content)`
- [X] `getEntity(string $type, string $identifier, bool $onlyEnabled)`
- [X] `removeMissingEntities(string $type, array $identifiers)`
- [X] `createNewEntity(string $type, string $identifier, string $parentIdentifier)`
- [X] `updateStructure(Pegass $entity)`
- [X] `updateVolunteer(Pegass $entity)`
- [X] `debug(Pegass $entity, string $message, array $data)`
- [X] `dispatchEvent(Pegass $entity)`

### App\Manager\PhoneConfigManager

<!-- src/Manager/PhoneConfigManager.php -->

- [X] `getPhoneConfigForVolunteer(Volunteer $volunteer)`
- [X] `getPhoneConfig(string $countryCode)`
- [X] `isSMSTransmittable(Volunteer $volunteer)`
- [X] `isVoiceCallTransmittable(Volunteer $volunteer)`
- [X] `restoreContext()`
- [X] `createCountryObject(array $row)`

### App\Manager\RefreshManager

<!-- src/Manager/RefreshManager.php -->

- [X] `refresh(bool $force)`
- [X] `refreshStructures(bool $force)`
- [X] `refreshVolunteers(bool $force)`
- [X] `debug(string $message, array $params)`
- [X] `refreshStructure(Pegass $pegass, bool $force)`
- [X] `refreshParentStructures()`
- [X] `refreshVolunteer(Pegass $pegass, bool $force)`
- [X] `checkRTMRRole(Volunteer $volunteer)`
- [X] `normalizeName(?string $name)`
- [X] `fetchPhoneNumber(Volunteer $volunteer, string $phoneNumber)`
- [X] `fetchBadges(Pegass $pegass)`
- [X] `fetchActionBadges(array $data)`
- [X] `fetchSkillBadges(array $data)`
- [X] `fetchTrainingBadges(array $data)`
- [X] `fetchNominationBadges(array $data)`
- [X] `createBadge(string $externalId, string $name, ?string $description)`
- [X] `refreshAsync()`

### App\Manager\ReportManager

<!-- src/Manager/ReportManager.php -->

- [X] `createReports(OutputInterface $output)`
- [X] `createReport(Communication $communication)`
- [X] `createStructureReport(\DateTime $from, \DateTime $to, int $minMessages)`
- [X] `createRepartition(Communication $communication, Report $report)`
- [X] `incrementCounters(Message $message, bool $communicationHasChoices, AbstractReport $entity)`
- [X] `calculateMessageCosts(Message $message, array $costs)`
- [X] `saveCommunicationReport(Communication $communication)`
- [X] `createUserStructuresCostsReport(array $structureIds, \DateTime $from, \DateTime $to)` — Creates a detailed cost
  report for specific structures within a date range.
- [X] `createUserStructuresMonthlyTotals(array $structureIds, int $months)` — Creates monthly cost totals for specific
  structures over the last N months.

### App\Manager\StatisticsManager

<!-- src/Manager/StatisticsManager.php -->

- [X] `getDashboardStatistics(\DateTime $from, \DateTime $to, Structure $structure)` — Returns all statistics for the
  dashboard

### App\Manager\StructureManager

<!-- src/Manager/StructureManager.php -->

- [X] `searchForCurrentUser(?string $criteria, int $maxResults)`
- [X] `searchForCurrentUserQueryBuilder(?string $criteria, bool $enabled)`
- [X] `searchQueryBuilder(?string $criteria, bool $enabled)`
- [X] `searchAllForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria, bool $enabled)`
- [X] `searchForVolunteerAndCurrentUserQueryBuilder(Volunteer $volunteer, ?string $criteria, bool $enabled)`
- [X] `searchForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria, bool $enabled)`
- [X] `getStructuresForUser(User $user)`
- [X] `countRedCallUsersInPager(Pagerfanta $pagerfanta)`
- [X] `addStructureAndItsChildrenToVolunteer(Volunteer $volunteer, Structure $structure)`

### App\Manager\TemplateImageManager

<!-- src/Manager/TemplateImageManager.php -->

- [X] `handleImages(Template $template, string $body)`

### App\Manager\TemplateManager

<!-- src/Manager/TemplateManager.php -->

- [X] `findByTypeForCurrentUser(Type $type)`

### App\Manager\UserManager

<!-- src/Manager/UserManager.php -->

- [X] `changeLocale(User $user, string $locale)`
- [X] `changeVolunteer(User $user, ?string $volunteerExternalId)`
- [X] `getRedCallUsersInStructure(Structure $structure, bool $includeChildren)`
- [X] `createUser(string $externalId)`

### App\Manager\VolunteerManager

<!-- src/Manager/VolunteerManager.php -->

- [X] `findOneByPhoneNumber(string $phoneNumber)`
- [X] `searchForCurrentUser(?string $criteria, int $limit, bool $onlyEnabled)`
- [X] `getVolunteerList(array $volunteerIds, bool $onlyEnabled)`
- [X] `getVolunteerListForCurrentUser(array $volunteerIds)`
- [X] 
  `searchInStructureQueryBuilder(Structure $structure, ?string $criteria, bool $onlyEnabled, bool $onlyUsers, bool $includeHierarchy, bool $onlyLocked)`
- [X] `searchQueryBuilder(?string $criteria, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `searchAllQueryBuilder(?string $criteria, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `searchForCurrentUserQueryBuilder(?string $criteria, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `getIdsByExternalIds(array $externalIds)`
- [X] `filterInvalidExternalIds(array $externalIds)`
- [X] `filterInaccessibles(array $volunteerIds)`
- [X] `anonymize(Volunteer $volunteer)`
- [X] `save(Volunteer $volunteer)`
- [X] `orderVolunteerIdsByTriggeringPriority(array $volunteerIds)`
- [X] 
  `getVolunteersFromList(VolunteerList $list, ?string $criteria, bool $hideDisabled, bool $filterUsers, bool $filterLocked, array $structures)`

### App\Manager\VolunteerSessionManager

<!-- src/Manager/VolunteerSessionManager.php -->

- [X] `createSession(Volunteer $volunteer)`

## Entity (176 methods)

### App\Entity\Answer

<!-- src/Entity/Answer.php -->

- [X] `getChoiceLabels()`
- [X] `addChoice(Choice $choice)`
- [X] `removeChoice(Choice $choice)`
- [X] `onPrePersist()`
- [X] `onPreUpdate()`

### App\Entity\Badge

<!-- src/Entity/Badge.php -->

- [X] `getVolunteers(bool $onlyEnabled)`
- [X] `addVolunteer(Volunteer $volunteer)`
- [X] `removeVolunteer(Volunteer $volunteer)`
- [X] `getSynonym()`
- [X] `getChildren(bool $onlyEnabled)`
- [X] `addChild(self $child)`
- [X] `removeChild(self $child)`
- [X] `getFullName()`
- [X] `toSearchResults()`
- [X] `getSynonyms(bool $onlyEnabled)`
- [X] `addSynonym(self $synonym)`
- [X] `removeSynonym(self $synonym)`
- [X] `canBeRemoved()`
- [X] `getCoveringBadges(int $stop)`
- [X] `getCoveredBadges()`
- [X] `isUsable()`
- [X] `validate(ExecutionContextInterface $context, $payload)`
- [X] `isParentLooping()`
- [X] `isSynonymLooping()`
- [X] `sortBadges(Badge $a, Badge $b)`

### App\Entity\Campaign

<!-- src/Entity/Campaign.php -->

- [X] `getCode()`
- [X] `getCommunicationByType(string $type)`
- [X] `addCommunication(Communication $communication)`
- [X] `getCampaignStatus(TranslatorInterface $translator)`
- [X] `getCampaignProgression()`
- [X] `getCost()`
- [X] `isReportReady()`
- [X] `hasChoices()`
- [X] `getRenderedShortcuts()`

### App\Entity\Category

<!-- src/Entity/Category.php -->

- [X] `getBadges(bool $onlyEnabled)`
- [X] `addBadge(Badge $badge)`
- [X] `removeBadge(Badge $badge)`
- [X] `toSearchResults()`

### App\Entity\Communication

<!-- src/Entity/Communication.php -->

- [X] `getLimitedBody(int $limit)`
- [X] `addMessage(Message $message)`
- [X] `addChoice(Choice $choice)`
- [X] `getChoiceByCode(?string $prefix, string $code)`
- [X] `getAllChoicesInText(?string $prefix, string $raw)`
- [X] `getFirstChoice()`
- [X] `getChoiceByLabel(string $label)`
- [X] `isUnclear(?string $prefix, string $message)`
- [X] `getEstimatedCost(string $body)`
- [X] `getPartitionedMessages()` — Partitions messages into two groups in a single pass:
- [X] `computeChoiceCounts()` — Computes all choice counts in a single pass over messages,
- [X] `getInvalidAnswersCount()`
- [X] `noAnswersCount()`
- [X] `countReachables()` — A volunteer is reachable if:
- [X] `getSendTaskName()`
- [X] `getProgression()`
- [X] `addImage(Media $image)`
- [X] `removeImage(Media $image)`
- [X] `getLastAnswerTime(?Choice $choice)`
- [X] `getCost()`

### App\Entity\Cost

<!-- src/Entity/Cost.php -->

- [X] `onPrePersist()`

### App\Entity\Message

<!-- src/Entity/Message.php -->

- [X] `getCost()`
- [X] `removeAnswer(Answer $answer)`
- [X] `getCode()`
- [X] `getAnswerByChoice(Choice $choice)`
- [X] `getLastAnswer(bool $includingAdmins)`
- [X] `getInvalidAnswer()`
- [X] `hasValidAnswer()` — Returns invalid answers only if no valid answer has been ticked.
- [X] `isUnclear()`
- [X] `getUnclear()`
- [X] `getChoices()`
- [X] `addCost(Cost $cost)`
- [X] `removeCost(Cost $cost)`
- [X] `isReachable()` — This signature is used to replace CSRF tokens on answer links sent by email.
- [X] `shouldAddMinutisResource()`
- [X] `shouldRemoveMinutisResource()`

### App\Entity\Operation

<!-- src/Entity/Operation.php -->

- [X] `addChoice(Choice $choice)`
- [X] `removeChoice(Choice $choice)`
- [X] `setCampaign(?Campaign $campaign)`

### App\Entity\Pegass

<!-- src/Entity/Pegass.php -->

- [X] `setIdentifier(string $identifier)`
- [X] `setParentIdentifier(?string $parentIdentifier)`
- [X] `evaluate(string $expression)`
- [X] `getXml()`
- [X] `xpath(string $template, array $parameters)`
- [X] `xpathQuote(string $value)` — Credits:
- [X] `toXml(array $arr, string $name_for_numeric_keys, int $nest)` — Credits:
- [X] `prePersist()`

### App\Entity\Phone

<!-- src/Entity/Phone.php -->

- [X] `addVolunteer(Volunteer $volunteer)`
- [X] `removeVolunteer(Volunteer $volunteer)`
- [X] `getHidden()`
- [X] `populateFromE164()`

### App\Entity\PrefilledAnswers

<!-- src/Entity/PrefilledAnswers.php -->

- [X] `sanitizePFAs()`
- [X] `restorePFAs()`

### App\Entity\Report

<!-- src/Entity/Report.php -->

- [X] `addRepartition(ReportRepartition $costRepartition)`
- [X] `removeRepartition(ReportRepartition $costRepartition)`
- [X] `getCosts()`
- [X] `setCommunication(?Communication $communication)`

### App\Entity\ReportRepartition

<!-- src/Entity/ReportRepartition.php -->

- [X] `getCosts()`

### App\Entity\Session

<!-- src/Entity/Session.php -->

- [X] `setLifetime(int $lifetime)`

### App\Entity\Structure

<!-- src/Entity/Structure.php -->

- [X] `getVolunteer(string $externalId)`
- [X] `getVolunteers(bool $onlyEnabled)`
- [X] `addVolunteer(Volunteer $volunteer)`
- [X] `removeVolunteer(Volunteer $volunteer)`
- [X] `getAncestors()`
- [X] `addChildrenStructure(self $childrenStructure)`
- [X] `removeChildrenStructure(self $childrenStructure)`
- [X] `addUser(User $user)`
- [X] `removeUser(User $user)`
- [X] `addPrefilledAnswer(PrefilledAnswers $prefilledAnswer)`
- [X] `removePrefilledAnswer(PrefilledAnswers $prefilledAnswers)`
- [X] `getPresidentVolunteer()`
- [X] `getNextPegassUpdate()`
- [X] `toSearchResults()`
- [X] `validate(ExecutionContextInterface $context, $payload)`
- [X] `isParentLooping()`
- [X] `getParentHierarchy(int $stop)`
- [X] `addVolunteerList(VolunteerList $volunteerList)`
- [X] `removeVolunteerList(VolunteerList $volunteerList)`
- [X] `getVolunteerList(string $name)`
- [X] `addTemplate(Template $template)`
- [X] `removeTemplate(Template $template)`

### App\Entity\Template

<!-- src/Entity/Template.php -->

- [X] `getBodyWithImages()`
- [X] `addImage(TemplateImage $image)`
- [X] `removeImage(TemplateImage $image)`
- [X] `validate(ExecutionContextInterface $context, $payload)`

### App\Entity\has

<!-- src/Entity/User.php -->

- [X] `setVolunteer(?Volunteer $volunteer)`
- [X] `getStructuresAsList()`
- [X] `getStructures(bool $onlyEnabled)`
- [X] `getStructuresShortcuts()`
- [X] `removeStructure(Structure $structure)`
- [X] `updateStructures(array $structures)`
- [X] `addStructure(Structure $structure)`
- [X] `hasCommonStructure($structures)`
- [X] `getCommonStructures($structures)`
- [X] `getMainStructure()`
- [X] `getDisplayName()`
- [X] `getRoles()`
- [X] `getSortedFavoriteBadges()`
- [X] `addFavoriteBadge(Badge $favoriteBadge)`

### App\Entity\Volunteer

<!-- src/Entity/Volunteer.php -->

- [X] `getPhone()`
- [X] `getPhoneByNumber(string $e164)`
- [X] `hasPhoneNumber(string $phoneNumber)`
- [X] `getDisplayName()`
- [X] `isCallable()`
- [X] `getStructureIds()`
- [X] `getStructures(bool $onlyEnabled)`
- [X] `addStructure(Structure $structure)`
- [X] `removeStructure(Structure $structure)`
- [X] `syncStructures(array $newStructures)`
- [X] `getMainStructure(bool $onlyEnabled)`
- [X] `toSearchResults(?User $user)`
- [X] `getVisibleBadges(?User $user)`
- [X] `getBadgesFilteredFromSynonyms()`
- [X] `getBadges(bool $onlyEnabled)`
- [X] `setBadges(array $badges)`
- [X] `getNextPegassUpdate()`
- [X] `getTruncatedName()`
- [X] `toName(string $name)`
- [X] `getUser()`
- [X] `shouldBeLocked(Volunteer $volunteer)`
- [X] `getHiddenPhone()`
- [X] `getHiddenEmail()`
- [X] `doNotDisableRedCallUsers(ExecutionContextInterface $context, $payload)`
- [X] `removeBadge(Badge $badge)`
- [X] `addList(VolunteerList $list)`
- [X] `removeList(VolunteerList $list)`
- [X] `removeExpiredBadges()`
- [X] `hasBadge(string $badgeName)`
- [X] `setExternalBadges(array $badges)`
- [X] `addBadge(Badge $badge)`
- [X] `validate(ExecutionContextInterface $context, $payload)`
- [X] `getBadgePriority(?User $user)`
- [X] `addPhone(Phone $phone)`
- [X] `setPhoneAsPreferred(Phone $phone)`
- [X] `removePhone(Phone $phone)`
- [X] `ensureOnePhoneIsPreferred()`
- [X] `needsShortcutInMessages()`

### App\Entity\VolunteerList

<!-- src/Entity/VolunteerList.php -->

- [X] `addVolunteer(Volunteer $volunteer)`

## Tools (9 methods)

### App\Tools\Encryption

<!-- src/Tools/Encryption.php -->

- [X] `encrypt(string $cleartext, string $salt)`
- [X] `decrypt(string $encrypted, string $salt)`

### App\Tools\GSM

<!-- src/Tools/GSM.php -->

- [X] `isGSMCompatible(string $message)`
- [X] `transliterate(string $message)`
- [X] `enforceGSMAlphabet(string $message)`
- [X] `getSMSParts(string $message)`

### App\Tools\Random

<!-- src/Tools/Random.php -->

- [X] `generate($size, $base)`
- [X] `between(int $a, int $b)`
- [X] `filtered(int $size, string $regexp)`

## Services (47 methods)

### App\Services\InstancesNationales\LogService

<!-- src/Services/InstancesNationales/LogService.php -->

- [X] `success(string $type, string $message, array $parameters)`
- [X] `error(string $message, array $parameters)`
- [X] `push(string $message, array $parameters, bool $impactful)`
- [X] `colorize(?bool $value)`
- [X] `pass(string $message, array $parameters, bool $impactful)`
- [X] `fail(string $message, array $parameters, bool $impactful)`
- [X] `flush()`
- [X] `dump(bool $return)`
- [X] `getFormattedDebug()`

### App\Services\InstancesNationales\UserService

<!-- src/Services/InstancesNationales/UserService.php -->

- [X] `extractUsers()`
- [X] `extractUsersFromGSheets()`
- [X] `extractObjectsFromGrid(SheetsExtract $extract)`
- [X] `deleteMissingUsers(Structure $structure, UsersExtract $extract)`
- [X] `createUsers(Structure $structure, UsersExtract $extract)`

### App\Services\InstancesNationales\VolunteerService

<!-- src/Services/InstancesNationales/VolunteerService.php -->

- [X] `extractVolunteers()`
- [X] `extractVolunteersFromGSheets()`
- [X] `extractObjectsFromGrid(SheetExtract $extract)`
- [X] `filterVolunteers(VolunteersExtract $volunteers, SheetExtract $list)`
- [X] `deleteMissingVolunteers(Structure $structure, VolunteersExtract $extract)`
- [X] `crupdateVolunteers(Structure $structure, VolunteersExtract $extract)`
- [X] `createLists(Structure $structure, SheetExtract $extract)`
- [X] `populatePhone(VolunteerExtract $extract, ?string $phoneNumber, string $letter, int $index)`
- [X] `populateEmail(VolunteerExtract $extract, ?string $email, string $letter, int $index)`

### App\Services\MessageFormatter

<!-- src/Services/MessageFormatter.php -->

- [X] `formatMessageContent(Message $message)`
- [X] `formatSMSContent(Message $message)`
- [X] `formatSimpleSMSContent(Volunteer $volunteer, string $content)`
- [X] `formatCallContent(Message $message, bool $withChoices)`
- [X] `formatCallChoicesContent(Message $message)`
- [X] `formatTextEmailContent(Message $message)`
- [X] `formatHtmlEmailContent(Message $message)`

### App\Services\Mjml

<!-- src/Services/Mjml.php -->

- [X] `convert(string $mjml)`
- [X] `getClient()`

### App\Services\Phrase

<!-- src/Services/Phrase.php -->

- [X] `getLocales()`
- [X] `download(string $localeId, string $tag)`
- [X] `createTranslation(string $tag, string $localeId, string $key, string $value)`
- [X] `searchKey(string $key)`
- [X] `createKey(string $tag, string $key)`
- [X] `removeKey(string $key)`
- [X] `query(string $method, string $path, ?array $placeholders, ?array $queryParams, ?array $body)`
- [X] `getClient()`

### App\Services\TextToSpeech

<!-- src/Services/TextToSpeech.php -->

- [X] `textToSpeech(TextToSpeechConfig $config, string $text, bool $male)`
- [X] `getClient()`

### App\Services\VoiceCalls

<!-- src/Services/VoiceCalls.php -->

- [X] `establishCall(string $uuid, Message $message)`
- [X] `handleKeyPress(string $uuid, Message $message, string $digit)`
- [X] `prepareMedias(Communication $communication)`
- [X] `getInvalidAnswerResponse(string $uuid, Message $message)`
- [X] `getVoiceResponse(string $uuid, TextToSpeechConfig $config, string $text, string $gather)`

## Security (27 methods)

### App\Security\Authenticator\GoogleConnectAuthenticator

<!-- src/Security/Authenticator/GoogleConnectAuthenticator.php -->

- [X] `supports(Request $request)` — Used to create a session
- [X] `getUser($request, UserProviderInterface $userProvider)`
- [X] `onAuthenticationFailure(Request $request, AuthenticationException $exception)`
- [X] `onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)`
- [X] `start(Request $request, AuthenticationException $authException)`

### App\Security\Authenticator\MinutisAuthenticator

<!-- src/Security/Authenticator/MinutisAuthenticator.php -->

- [X] `supports(Request $request)`
- [X] `getCredentials(Request $request)`
- [X] `getUser($jwt, UserProviderInterface $userProvider)`
- [X] `onAuthenticationFailure(Request $request, AuthenticationException $exception)`
- [X] `onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)`
- [X] `start(Request $request, AuthenticationException $authException)`
- [X] `getMinutisPublicKey()`

### App\Security\Authenticator\NivolAuthenticator

<!-- src/Security/Authenticator/NivolAuthenticator.php -->

- [X] `start(Request $request, AuthenticationException $authException)`
- [X] `supports(Request $request)`
- [X] `getCredentials(Request $request)`
- [X] `getUser($credentials, UserProviderInterface $userProvider)`
- [X] `checkCredentials($credentials, UserInterface $user)`
- [X] `onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)`

### App\Security\Voter\BadgeVoter

<!-- src/Security/Voter/BadgeVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\CampaignVoter

<!-- src/Security/Voter/CampaignVoter.php -->

- [X] `supports($attribute, $subject)`
- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\CategoryVoter

<!-- src/Security/Voter/CategoryVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\CommunicationVoter

<!-- src/Security/Voter/CommunicationVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\StructureVoter

<!-- src/Security/Voter/StructureVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\UserVoter

<!-- src/Security/Voter/UserVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\VolunteerSessionVoter

<!-- src/Security/Voter/VolunteerSessionVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

### App\Security\Voter\VolunteerVoter

<!-- src/Security/Voter/VolunteerVoter.php -->

- [X] `voteOnAttribute($attribute, $subject, TokenInterface $token)`

## Communication (8 methods)

### App\Communication\Processor\ExecProcessor

<!-- src/Communication/Processor/ExecProcessor.php -->

- [X] `process(Communication $communication)`

### App\Communication\Processor\TaskProcessor

<!-- src/Communication/Processor/TaskProcessor.php -->

- [X] `process(Communication $communication)`

### App\Communication\Sender

<!-- src/Communication/Sender.php -->

- [X] `sendCommunication(Communication $communication, bool $force)`
- [X] `sendMessage(Message $message, bool $sleep)`
- [X] `isMessageNotTransmittable(Message $message)`
- [X] `sendSms(Message $message)`
- [X] `sendCall(Message $message)`
- [X] `sendEmail(Message $message)`

## Validator (8 methods)

### App\Communication\Processor\ExecProcessor

<!-- src/Communication/Processor/ExecProcessor.php -->

- [X] `process(Communication $communication)`

### App\Communication\Processor\TaskProcessor

<!-- src/Communication/Processor/TaskProcessor.php -->

- [X] `process(Communication $communication)`

### App\Communication\Sender

<!-- src/Communication/Sender.php -->

- [X] `sendCommunication(Communication $communication, bool $force)`
- [X] `sendMessage(Message $message, bool $sleep)`
- [X] `isMessageNotTransmittable(Message $message)`
- [X] `sendSms(Message $message)`
- [X] `sendCall(Message $message)`
- [X] `sendEmail(Message $message)`

## Repository (177 methods)

### App\Repository\AnswerRepository

<!-- src/Repository/AnswerRepository.php -->

- [X] `clearAnswers(Message $message)`
- [X] `clearChoices(Message $message, array $choices)`
- [X] `getSearchQueryBuilder(string $criteria)`
- [X] `getVolunteerAnswersQueryBuilder(Volunteer $volunteer)`

### App\Repository\BadgeRepository

<!-- src/Repository/BadgeRepository.php -->

- [X] `findOneByExternalId(string $externalId)`
- [X] `findOneByName(string $name)`
- [X] `getSearchInBadgesQueryBuilder(?string $criteria, bool $onlyEnabled)`
- [X] `getVolunteerCountInBadgeList(array $ids)`
- [X] `searchForCompletion(?string $criteria, int $limit)`
- [X] `searchNonVisibleUsableBadge(?string $criteria, int $limit)`
- [X] `getNonVisibleUsableBadgesList(array $ids)`
- [X] `getBadgesInCategoryQueryBuilder(Category $category)`
- [X] `searchForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria)`
- [X] `getBadgesQueryBuilder()`
- [X] `addSearchCriteria(QueryBuilder $qb, string $criteria)`

### App\Repository\CampaignRepository

<!-- src/Repository/CampaignRepository.php -->

- [X] `findOneByIdNoCache(int $campaignId)`
- [X] `getCampaignsOpenedByMeOrMyCrew(AbstractUser $user)`
- [X] `getCampaignImpactingMyVolunteers(AbstractUser $user)`
- [X] `getInactiveCampaignsForUserQueryBuilder(AbstractUser $user)`
- [X] `closeCampaign(Campaign $campaign)`
- [X] `openCampaign(Campaign $campaign)`
- [X] `changeColor(Campaign $campaign, string $color)`
- [X] `changeName(Campaign $campaign, string $newName)`
- [X] `changeNotes(Campaign $campaign, string $notes)`
- [X] `countAllOpenCampaigns()`
- [X] `closeExpiredCampaigns()`
- [X] `getNoteUpdateTimestamp(int $campaignId)`
- [X] `countNumberOfMessagesSent(int $campaignId)`
- [X] `countNumberOfAnswersReceived(int $campaignId)`
- [X] `getCampaignAudience(Campaign $campaign)`

### App\Repository\CategoryRepository

<!-- src/Repository/CategoryRepository.php -->

- [X] `findOneByExternalId(string $externalId)`
- [X] `getSearchInCategoriesQueryBuilder(?string $criteria)`
- [X] `search(?string $criteria, int $limit)`
- [X] `addSearchCriteria(QueryBuilder $qb, string $criteria)`

### App\Repository\CommunicationRepository

<!-- src/Repository/CommunicationRepository.php -->

- [X] `changeName(Communication $communication, string $newName)`
- [X] `findCommunicationIdsRequiringReports(\DateTime $date)`
- [X] `getCommunicationStructures(Communication $communication)`

### App\Repository\CostRepository

<!-- src/Repository/CostRepository.php -->

- [X] `truncate()`

### App\Repository\DeletedVolunteerRepository

<!-- src/Repository/DeletedVolunteerRepository.php -->

- [X] `add(DeletedVolunteer $entity, bool $flush)`
- [X] `remove(DeletedVolunteer $entity, bool $flush)`
- [X] `findByExampleField($value)`
- [X] `findOneBySomeField($value)`

### App\Repository\ExpirableRepository

<!-- src/Repository/ExpirableRepository.php -->

- [X] `clearExpired()`

### App\Repository\MediaRepository

<!-- src/Repository/MediaRepository.php -->

- [X] `save(Media $media)`
- [X] `clearExpired()`

### App\Repository\MessageRepository

<!-- src/Repository/MessageRepository.php -->

- [X] `updateMessageStatus(Message $message)`
- [X] `getMessageFromPhoneNumber(string $phoneNumber)`
- [X] `getMessageFromPhoneNumberAndPrefix(string $phoneNumber, string $prefix)`
- [X] `cancelAnswerByChoice(Message $message, Choice $choice)`
- [X] `findOneByIdNoCache(int $messageId)`
- [X] `refresh(Message $message)`
- [X] `getNumberOfSentMessages(Campaign $campaign)`
- [X] `findUsedCodes(array $codes)`
- [X] `getUsedPrefixes(array $volunteers)`
- [X] `canUsePrefixesForEveryone(array $volunteersTakenPrefixes)`
- [X] `getLatestMessageUpdated()`
- [X] `getActiveMessagesForVolunteer(Volunteer $volunteer)`
- [X] `getLatestMessagesForVolunteer(Volunteer $volunteer)`

### App\Repository\OperationRepository

<!-- src/Repository/OperationRepository.php -->

- [X] `findAll()`
- [X] `findByExampleField($value)`
- [X] `findOneBySomeField($value)`

### App\Repository\PegassRepository

<!-- src/Repository/PegassRepository.php -->

- [X] `getEntity(string $type, string $identifier, bool $onlyEnabled)`
- [X] `findMissingEntities(string $type, array $identifiers, ?string $parentIdentifier)`
- [X] `findAllChildrenEntities(string $type, string $parentIdentifier)`
- [X] `foreach(string $type, callable $callback, bool $onlyEnabled)`
- [X] `getAllEnabledEntities()`
- [X] `getEnabledEntitiesQueryBuilder(?string $type, ?string $identifier)`
- [X] `save(Pegass $entity)`
- [X] `delete(Pegass $entity)`

### App\Repository\PhoneRepository

<!-- src/Repository/PhoneRepository.php -->

- [X] `findOneByPhoneNumber(string $phoneNumber)`
- [X] `findOneByVolunteerAndE164(string $externalId, string $e164)` — Used in VolunteerController::phoneRemove

### App\Repository\PrefilledAnswersRepository

<!-- src/Repository/PrefilledAnswersRepository.php -->

- [X] `getPrefilledAnswersByStructure(Structure $structure)`
- [X] `findByUserForStructureAndGlobal(User $user)`

### App\Repository\RememberMeRepository

<!-- src/Repository/RememberMeRepository.php -->

- [X] `findByExampleField($value)`
- [X] `findOneBySomeField($value)`

### App\Repository\ReportRepartitionRepository

<!-- src/Repository/ReportRepartitionRepository.php -->

- [X] `save(ReportRepartition $report)`

### App\Repository\ReportRepository

<!-- src/Repository/ReportRepository.php -->

- [X] `save(Report $report)`
- [X] `getCommunicationReportsBetween(\DateTime $from, \DateTime $to, int $minMessages)`
- [X] `getCostsReportByStructures(array $structureIds, \DateTime $from, \DateTime $to)` — Native SQL query to get costs
  report for specific structures within a date ra...
- [X] `getMonthlyTotalsByStructures(array $structureIds, \DateTime $from, \DateTime $to)` — Native SQL query to get
  monthly cost totals for specific structures over mult...
- [X] `getStructureReportData(\DateTime $from, \DateTime $to, int $minMessages)` — Native SQL query for structure
  reports (admin statistics).

### App\Repository\StatisticsRepository

<!-- src/Repository/StatisticsRepository.php -->

- [X] `getNumberOfCampaigns(\DateTime $from, \DateTime $to)` — Warning: opposite to other repositories, this one
- [X] `getEmailAndPhoneNumberMissings()`
- [X] `getVolunteerPegassUpdate()` — Return first and last pegass update for volunteers
- [X] `getStructurePegassUpdate()` — Return first and last pegass update for structures
- [X] `getNumberOfSentMessagesByKind(\DateTime $from, \DateTime $to)` — Return all sent messages and group by kind (
  communication.type)
- [X] `getNumberOfTriggeredVolounteers(\DateTime $from, \DateTime $to)` — Return all triggered volounteers
- [X] `getNumberOfAnswersReceived(\DateTime $from, \DateTime $to)` — Return all answers received
- [X] `getSumOfCost(\DateTime $from, \DateTime $to)` — Return the costs grouped by c.direction

### App\Repository\StructureRepository

<!-- src/Repository/StructureRepository.php -->

- [X] `findOneByExternalId(string $externalId)`
- [X] `findOneByName(string $name)`
- [X] `findCallableStructuresForVolunteer(Volunteer $volunteer)` — This method perform nested search of all volunteer's
  structures
- [X] `findCallableStructuresForStructure(Structure $structure)` — This method performs nested search of all children
  structures of a structure
- [X] `getStructuresForUserQueryBuilder(User $user)`
- [X] `searchAllQueryBuilder(?string $criteria, bool $onlyEnabled)`
- [X] `searchAll(?string $criteria, int $maxResults)`
- [X] `searchForUserQueryBuilder(User $user, ?string $criteria, bool $onlyEnabled)`
- [X] `synchronizeWithPegass()`
- [X] `getCampaignStructures(Campaign $campaign)`
- [X] `countRedCallUsersQueryBuilder(QueryBuilder $qb)`
- [X] `getStructureHierarchyForCurrentUser(User $user)`
- [X] `getVolunteerLocalCounts(array $structureIds)`
- [X] `getDescendantStructures(array $structureIds)`
- [X] `searchAllForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria, bool $enabled)`
- [X] `searchForVolunteerAndCurrentUserQueryBuilder(User $user, Volunteer $volunteer, ?string $criteria, bool $enabled)`
- [X] `forVolunteer(QueryBuilder $qb, Volunteer $volunteer)`

### App\Repository\TemplateImageRepository

<!-- src/Repository/TemplateImageRepository.php -->

- [X] `add(TemplateImage $entity, bool $flush)`
- [X] `remove(TemplateImage $entity, bool $flush)`
- [X] `findByExampleField($value)`
- [X] `findOneBySomeField($value)`

### App\Repository\TemplateRepository

<!-- src/Repository/TemplateRepository.php -->

- [X] `add(Template $entity, bool $flush)`
- [X] `remove(Template $entity, bool $flush)`
- [X] `getTemplatesForStructure(Structure $structure)`
- [X] `findByTypeForUserStructures(User $user, Type $type)`

### App\Repository\UserRepository

<!-- src/Repository/UserRepository.php -->

- [X] `save(AbstractUser $user)`
- [X] `remove(AbstractUser $user)`
- [X] `findOneByExternalId(string $externalId)`
- [X] `findOneByUsername(string $username)`
- [X] `searchQueryBuilder(?string $criteria, ?bool $onlyAdmins)`
- [X] `getRedCallUsersInStructure(Structure $structure)`
- [X] `createTrustedUserQueryBuilder()`
- [X] `findAllWithStructure(Structure $structure)`

### App\Repository\VolunteerGroupRepository

<!-- src/Repository/VolunteerGroupRepository.php -->

- [X] `save(VolunteerGroup $entity, bool $flush)`
- [X] `remove(VolunteerGroup $entity, bool $flush)`
- [X] `getVolunteerGroups(int $campaignId)`
- [X] `countVolunteerGroups(int $campaignId)`

### App\Repository\VolunteerListRepository

<!-- src/Repository/VolunteerListRepository.php -->

- [X] `findVolunteerListsForUser(User $user)`

### App\Repository\VolunteerRepository

<!-- src/Repository/VolunteerRepository.php -->

- [X] `findOneByInternalEmail(string $internalEmail)`
- [X] `disable(Volunteer $volunteer)`
- [X] `enable(Volunteer $volunteer)`
- [X] `lock(Volunteer $volunteer)`
- [X] `unlock(Volunteer $volunteer)`
- [X] `findOneByExternalId(string $externalId)`
- [X] `searchForUser(User $user, ?string $keyword, int $maxResults, bool $onlyEnabled)`
- [X] `searchForUserQueryBuilder(User $user, ?string $keyword, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `createAccessibleVolunteersQueryBuilder(User $user, bool $enabled)`
- [X] `addSearchCriteria(QueryBuilder $qb, string $criteria)`
- [X] `createVolunteersQueryBuilder(bool $enabled)`
- [X] `searchAll(?string $keyword, int $maxResults, bool $enabled)`
- [X] `searchAllQueryBuilder(?string $keyword, bool $enabled)`
- [X] 
  `searchInStructureQueryBuilder(Structure $structure, ?string $keyword, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] 
  `searchInStructuresQueryBuilder(array $structureIds, ?string $keyword, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `searchAllWithFiltersQueryBuilder(?string $criteria, bool $onlyEnabled, bool $onlyUsers, bool $onlyLocked)`
- [X] `foreach(callable $callback, ?string $filters)`
- [X] `getIssues(User $user)`
- [X] `synchronizeWithPegass()`
- [X] `getIdsByExternalIds(array $externalIds)`
- [X] `filterInaccessibles(User $user, $volunteerIds)`
- [X] `filterInvalidExternalIds(array $externalIds)`
- [X] `getVolunteerList(array $volunteerIds, bool $onlyEnabled)`
- [X] `createVolunteerListQueryBuilder(array $volunteerIds, bool $onlyEnabled)`
- [X] `getVolunteerListForUser(User $user, array $volunteerIds)`
- [X] `getVolunteerListInStructures(array $structureIds)`
- [X] `getVolunteerListInStructuresQueryBuilder(array $structureIds)`
- [X] `getVolunteerCountInStructures(array $structureIds)`
- [X] `getVolunteerListInStructuresHavingBadgesQueryBuilder(array $structureIds, array $badgeIds)`
- [X] `getVolunteerListHavingBadgesQueryBuilder(array $badgeIds)`
- [X] `getVolunteerCountHavingBadgesQueryBuilder(array $badgeIds)`
- [X] `getVolunteerCountInStructuresHavingBadges(array $structureIds, array $badgeIds)`
- [X] `getVolunteerGlobalCounts(array $structureIds)`
- [X] `filterDisabled(array $volunteerIds)`
- [X] `filterOptoutUntil(array $volunteerIds)`
- [X] `filterPhoneLandline(array $volunteerIds)`
- [X] `filterPhoneMissing(array $volunteerIds)`
- [X] `filterPhoneOptout(array $volunteerIds)`
- [X] `filterEmailMissing(array $volunteerIds)`
- [X] `filterEmailOptout(array $volunteerIds)`
- [X] `filterMinors(array $volunteerIds)`
- [X] `getVolunteerTriggeringPriorities(array $volunteerIds)`
- [X] `getVolunteerCountInStructure(Structure $structure)`
- [X] `getVolunteersHavingBadgeQueryBuilder(Badge $badge)`
- [X] `findVolunteersToAnonymize()`
- [X] `countActive()`
- [X] 
  `getVolunteersFromList(VolunteerList $list, ?string $criteria, bool $hideDisabled, bool $filterUsers, bool $filterLocked, array $structureIds)`

### App\Repository\VolunteerSessionRepository

<!-- src/Repository/VolunteerSessionRepository.php -->

- [X] `clearExpired(int $expirationTtl)`
- [X] `findByExampleField($value)`
- [X] `findOneBySomeField($value)`

## Twig (4 methods)

### App\Twig\Extension\AppExtension

<!-- src/Twig/Extension/AppExtension.php -->

- [X] `getFunctions()`

### App\Twig\Extension\CommunicationExtension

<!-- src/Twig/Extension/CommunicationExtension.php -->

- [X] `getFilters()` — I first tried to use some
- [X] `getFormattedBody()` — I first tried to use something like this in Communication:
- [X] `formatEmail(Environment $environment, Communication $communication)`

## EventSubscriber (18 methods)

### App\EventSubscriber\CommunicationActivitySubscriber

<!-- src/EventSubscriber/CommunicationActivitySubscriber.php -->

- [X] `postPersist(LifecycleEventArgs $args)`
- [X] `postUpdate(LifecycleEventArgs $args)`
- [X] `onChange($entity)`

### App\EventSubscriber\ExceptionSubscriber

<!-- src/EventSubscriber/ExceptionSubscriber.php -->

- [X] `logException(ExceptionEvent $event)`

### App\EventSubscriber\LocaleSubscriber

<!-- src/EventSubscriber/LocaleSubscriber.php -->

- [X] `onKernelRequest(RequestEvent $event)`
- [X] `onInteractiveLogin(InteractiveLoginEvent $event)`

### App\EventSubscriber\TimezoneSubscriber

<!-- src/EventSubscriber/TimezoneSubscriber.php -->

- [X] `onKernelRequest(RequestEvent $event)`

### App\EventSubscriber\TwilioSubscriber

<!-- src/EventSubscriber/TwilioSubscriber.php -->

- [X] `onMessagePriceUpdated(TwilioMessageEvent $event)`
- [X] `onMessageReceived(TwilioMessageEvent $event)`
- [X] `onMessageError(TwilioMessageEvent $event)`
- [X] `onCallPriceUpdated(TwilioCallEvent $event)`
- [X] `onCallReceived(TwilioCallEvent $event)`
- [X] `onCallEstablished(TwilioCallEvent $event)`
- [X] `onCallKeyPressed(TwilioCallEvent $event)`
- [X] `onCallError(TwilioCallEvent $event)`
- [X] `onAnsweringMachine(TwilioCallEvent $event)`
- [X] `getMessageFromSms(TwilioMessageEvent $event)`
- [X] `getMessageFromCall(TwilioCallEvent $event)`

## Provider (26 methods)

### App\Provider\Call\Twilio

<!-- src/Provider/Call/Twilio.php -->

- [X] `send(string $from, string $to, array $context)`

### App\Provider\Email\Sendgrid

<!-- src/Provider/Email/Sendgrid.php -->

- [X] `send(string $to, string $subject, string $textBody, string $htmlBody)`

### App\Provider\Email\Symfony

<!-- src/Provider/Email/Symfony.php -->

- [X] `send(string $to, string $subject, string $textBody, string $htmlBody)`

### App\Provider\Minutis\Minutis

<!-- src/Provider/Minutis/Minutis.php -->

- [X] `searchForOperations(string $structureExternalId, string $criteria)`
- [X] `isOperationExisting(int $operationExternalId)`
- [X] `searchForVolunteer(string $volunteerExternalId)`
- [X] `createOperation(string $structureExternalId, string $name, string $ownerEmail)`
- [X] `addResourceToOperation(int $externalOperationId, string $volunteerExternalId)`
- [X] `removeResourceFromOperation(int $externalOperationId, int $resourceExternalId)`
- [X] `populateAuthentication(array $config)`
- [X] `getToken()`
- [X] `createToken()`
- [X] `getClient()`

### App\Provider\OAuth\GoogleConnect\GoogleConnect

<!-- src/Provider/OAuth/GoogleConnect/GoogleConnect.php -->

- [X] `getAuthorizationUri(string $redirectUri)`
- [X] `verify(Request $request)`
- [X] `getRedirectAfterAuthenticationUri(Request $request)`
- [X] `isQueryStringValid(Request $request)`
- [X] `isCsrfTokenValid(Request $request)`
- [X] `isRedirectUriValid(Request $request)`
- [X] `getAccessToken(Request $request)`
- [X] `getOauthUser(string $token)`
- [X] `getRedirectUri()`

### App\Provider\SMS\Twilio

<!-- src/Provider/SMS/Twilio.php -->

- [X] `send(string $from, string $to, string $message, array $context)`

### App\Provider\SMS\TwilioWithStatusAsTask

<!-- src/Provider/SMS/TwilioWithStatusAsTask.php -->

- [X] `send(string $from, string $to, string $message, array $context)`

### App\Provider\Storage\GoogleCloudStorage

<!-- src/Provider/Storage/GoogleCloudStorage.php -->

- [X] `store(string $filename, string $content)`
- [X] `getClient()`

## Task (19 methods)

### App\Task\AbstractSendMessageTask

<!-- src/Task/AbstractSendMessageTask.php -->

- [X] `execute(array $context)`

### App\Task\PegassCreateChunks

<!-- src/Task/PegassCreateChunks.php -->

- [X] `remote()`
- [X] `processFiles(array $context)`
- [X] `updateVolunteers()`
- [X] `updateStructures()`
- [X] `cleanMissingEntities()`
- [X] `extractNominations(array $csvs)`
- [X] `extractTrainings(array $csvs)`
- [X] `extractSkills(array $csvs)`
- [X] `extractActions(array $csvs)`
- [X] `extractVolunteerBasics(array $csvs)`
- [X] `extractStructureBasics(array $csvs)`
- [X] `decsvize(array $context)`

### App\Task\PegassUpdateChunk

<!-- src/Task/PegassUpdateChunk.php -->

- [X] `execute(array $context)`
- [X] `updateStructure(string $identifier, array $data)`
- [X] `updateVolunteer(string $identifier, array $data)`

### App\Task\SendCommunicationTask

<!-- src/Task/SendCommunicationTask.php -->

- [X] `execute(array $context)`

### App\Task\SyncAnnuaire

<!-- src/Task/SyncAnnuaire.php -->

- [X] `execute(array $context)`

### App\Task\SyncOneWithPegass

<!-- src/Task/SyncOneWithPegass.php -->

- [X] `execute(array $context)`

## Command (28 methods)

### App\Command\AnnuaireNationalCommand

<!-- src/Command/AnnuaireNationalCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`
- [X] `sendEmail()`

### App\Command\ClearCampaignCommand

<!-- src/Command/ClearCampaignCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\ClearExpirableCommand

<!-- src/Command/ClearExpirableCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\ClearMediaCommand

<!-- src/Command/ClearMediaCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\ClearSpaceCommand

<!-- src/Command/ClearSpaceCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\ClearVolunteerCommand

<!-- src/Command/ClearVolunteerCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\CreateUserCommand

<!-- src/Command/CreateUserCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)` — {

### App\Command\GenerateMjmlCommand

<!-- src/Command/GenerateMjmlCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`
- [X] `getCurrentHash(string $mjmlPath)`
- [X] `getOlderHash(string $mjmlPath)`

### App\Command\PegassFilesCommand

<!-- src/Command/PegassFilesCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\PegassSearchCommand

<!-- src/Command/PegassSearchCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)` — {

### App\Command\PhraseCommand

<!-- src/Command/PhraseCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`
- [X] `getPhraseTagFromFilename(string $absolutePath)` — On Phrase, I added tags for every translation files, they are
  in the format:
- [X] `getContextFromFilename(string $absolutePath)`
- [X] `getLocaleFromFileName(string $file)`
- [X] `getFilenameFromPhraseTag(string $tag, string $locale)`
- [X] `searchTranslationFilesInProject()` — All translation files in RedCall are in YAML format, and located in a
  transla...
- [X] `extractTranslationsFromFile(string $file)`
- [X] `downloadRemoteFile(string $localeId, string $tag)`
- [X] `getFlattenArrayFromTranslations(array $translations)` — Transforms a multidimensional array into a flatten one.
- [X] `getDeflattedTranslationsFromArray(array $array)`

### App\Command\RecoverCostsCommand

<!-- src/Command/RecoverCostsCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\RefreshCommand

<!-- src/Command/RefreshCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\ReportCommunicationCommand

<!-- src/Command/ReportCommunicationCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\SendCommunicationCommand

<!-- src/Command/SendCommunicationCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

### App\Command\UserRootCommand

<!-- src/Command/UserRootCommand.php -->

- [X] `execute(InputInterface $input, OutputInterface $output)`

## Component (1 methods)

### App\Component\HttpFoundation\ArrayToCsvResponse

<!-- src/Component/HttpFoundation/ArrayToCsvResponse.php -->

- [X] `arrayToCsv(array &$array)`

## Form (67 methods)

### App\Form\Extension\RegistrationTypeExtension

<!-- src/Form/Extension/RegistrationTypeExtension.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)` — {

### App\Form\Flow\CallTriggerFlow

<!-- src/Form/Flow/CallTriggerFlow.php -->

- [X] `loadStepsConfig()`

### App\Form\Flow\CampaignFlow

<!-- src/Form/Flow/CampaignFlow.php -->

- [X] `loadStepsConfig()`

### App\Form\Flow\EmailTriggerFlow

<!-- src/Form/Flow/EmailTriggerFlow.php -->

- [X] `loadStepsConfig()`

### App\Form\Flow\SmsTriggerFlow

<!-- src/Form/Flow/SmsTriggerFlow.php -->

- [X] `loadStepsConfig()`

### App\Form\Model\BaseTrigger

<!-- src/Form/Model/BaseTrigger.php -->

- [X] `removeOperationAnswer(string $answer)`
- [X] `jsonSerialize()`

### App\Form\Model\CallTrigger

<!-- src/Form/Model/CallTrigger.php -->

- [X] `validate(ExecutionContextInterface $context, $payload)`

### App\Form\Model\EmailTrigger

<!-- src/Form/Model/EmailTrigger.php -->

- [X] `validate(ExecutionContextInterface $context, $payload)`
- [X] `jsonSerialize()`

### App\Form\Model\SmsTrigger

<!-- src/Form/Model/SmsTrigger.php -->

- [X] `validate(ExecutionContextInterface $context, $payload)`

### App\Form\Type\AnswerType

<!-- src/Form/Type/AnswerType.php -->

- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\AudienceType

<!-- src/Form/Type/AudienceType.php -->

- [X] `getAudienceFormData(Request $request)`
- [X] `createEmptyData(array $defaults)`
- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `buildView(FormView $view, FormInterface $form, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`
- [X] `buildStructureView(FormView $view)`
- [X] `findDescendants(array &$hierarchy, array $children)`

### App\Form\Type\BadgeSelectionType

<!-- src/Form/Type/BadgeSelectionType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\BadgeWidgetType

<!-- src/Form/Type/BadgeWidgetType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `buildView(FormView $view, FormInterface $form, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\CallTriggerType

<!-- src/Form/Type/CallTriggerType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)` — {

### App\Form\Type\CampaignType

<!-- src/Form/Type/CampaignType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\CategoryWigetType

<!-- src/Form/Type/CategoryWigetType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `buildView(FormView $view, FormInterface $form, array $options)`

### App\Form\Type\ChooseCampaignOperationChoicesType

<!-- src/Form/Type/ChooseCampaignOperationChoicesType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\ChooseOperationChoicesType

<!-- src/Form/Type/ChooseOperationChoicesType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\CodeType

<!-- src/Form/Type/CodeType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\CreateCampaignOperationType

<!-- src/Form/Type/CreateCampaignOperationType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\CreateOperationType

<!-- src/Form/Type/CreateOperationType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\CreateOrUseOperationType

<!-- src/Form/Type/CreateOrUseOperationType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\EmailTriggerType

<!-- src/Form/Type/EmailTriggerType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)` — {
- [X] `extractImages(EmailTrigger $trigger)` — {

### App\Form\Type\LanguageType

<!-- src/Form/Type/LanguageType.php -->

- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\ManageUserStructuresType

<!-- src/Form/Type/ManageUserStructuresType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\NivolType

<!-- src/Form/Type/NivolType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\PhoneCardType

<!-- src/Form/Type/PhoneCardType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\PhoneCardsType

<!-- src/Form/Type/PhoneCardsType.php -->

- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\PrefilledAnswersType

<!-- src/Form/Type/PrefilledAnswersType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\SmsTriggerType

<!-- src/Form/Type/SmsTriggerType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)` — {

### App\Form\Type\StructureType

<!-- src/Form/Type/StructureType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\StructureWidgetType

<!-- src/Form/Type/StructureWidgetType.php -->

- [X] `buildView(FormView $view, FormInterface $form, array $options)`

### App\Form\Type\TemplateType

<!-- src/Form/Type/TemplateType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`

### App\Form\Type\TypesType

<!-- src/Form/Type/TypesType.php -->

- [X] `configureOptions(OptionsResolver $resolver)` — Class TypesType

### App\Form\Type\UseCampaignOperationType

<!-- src/Form/Type/UseCampaignOperationType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\UseOperationType

<!-- src/Form/Type/UseOperationType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\UserStructuresType

<!-- src/Form/Type/UserStructuresType.php -->

- [X] `configureOptions(OptionsResolver $resolver)`

### App\Form\Type\VolunteerListType

<!-- src/Form/Type/VolunteerListType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)` — {

### App\Form\Type\VolunteerType

<!-- src/Form/Type/VolunteerType.php -->

- [X] `buildForm(FormBuilderInterface $builder, array $options)`
- [X] `configureOptions(OptionsResolver $resolver)` — {

### App\Form\Type\VolunteerWidgetType

<!-- src/Form/Type/VolunteerWidgetType.php -->

- [X] `buildView(FormView $view, FormInterface $form, array $options)` — {

## Model (17 methods)

### App\Model\Classification

<!-- src/Model/Classification.php -->

- [X] `hasProblems()`

### App\Model\can

<!-- src/Model/Csrf.php -->

- [X] `someAction(Csrf $myToken)` — This class can be used inside a controller action in

### App\Model\InstancesNationales\SheetExtract

<!-- src/Model/InstancesNationales/SheetExtract.php -->

- [X] `fromArray(array $array)`
- [X] `fromRows(string $identifier, int $headerIndex, array $rows)`
- [X] `getColumn(string $columnName)`
- [X] `toArray()`
- [X] `getRow(array $identifier)`

### App\Model\InstancesNationales\SheetsExtract

<!-- src/Model/InstancesNationales/SheetsExtract.php -->

- [X] `toArray()`
- [X] `getTab(string $identifier)`
- [X] `fromArray(array $array)`

### App\Model\InstancesNationales\UserExtract

<!-- src/Model/InstancesNationales/UserExtract.php -->

- [X] `getNivol()`

### App\Model\InstancesNationales\VolunteerExtract

<!-- src/Model/InstancesNationales/VolunteerExtract.php -->

- [X] `getPhone()`
- [X] `isMobile(?string $phoneNumber)`
- [X] `getEmail()`

### App\Model\InstancesNationales\VolunteersExtract

<!-- src/Model/InstancesNationales/VolunteersExtract.php -->

- [X] `remove(VolunteerExtract $volunteer)`

### App\Model\MinutisToken

<!-- src/Model/MinutisToken.php -->

- [X] `unserialize(string $cypher)`

### App\Model\PhoneConfig

<!-- src/Model/PhoneConfig.php -->

- [X] `getOutboutSmsSenderByVolunteer(Volunteer $volunteer)`

## Enum (7 methods)

### App\Enum\Resource

<!-- src/Enum/Resource.php -->

- [X] `getManager()`
- [X] `getVoter()`
- [X] `getProviderMethod()`

### App\Enum\Type

<!-- src/Enum/Type.php -->

- [X] `getFormType()`
- [X] `getFormData()`
- [X] `getFormFlow()`
- [X] `getFormView()`

## ParamConverter (2 methods)

### App\ParamConverter\CsrfParamConverter

<!-- src/ParamConverter/CsrfParamConverter.php -->

- [X] `apply(Request $request, ParamConverter $configuration)`

### App\ParamConverter\EnumParamConverter

<!-- src/ParamConverter/EnumParamConverter.php -->

- [X] `apply(Request $request, ParamConverter $configuration)`

## Base (5 methods)

### App\Base\BaseController

<!-- src/Base/BaseController.php -->

- [X] `orderBy(QueryBuilder $qb, $class, $prefixedDefaultColumn, $defaultDirection, $prefix)`
- [X] `validateCsrfOrThrowNotFoundException(string $id, ?string $token)`

### App\Base\BaseRepository

<!-- src/Base/BaseRepository.php -->

- [X] `save($entity)`
- [X] `remove($entity)`

### App\Base\BaseService

<!-- src/Base/BaseService.php -->

- [X] `setContainer(ContainerInterface $container)`

## Logger (1 methods)

### App\Logger\ContextProcessor

<!-- src/Logger/ContextProcessor.php -->

- [X] `__invoke(LogRecord $record)`

