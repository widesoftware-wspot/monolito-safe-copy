<?php

require_once __DIR__ . '/../../../../../app/bootstrap.php.cache';
require_once __DIR__ . '/../../../../../app/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Question\Question;

$kernel = new AppKernel('prod', true);
$kernel->boot();

$application = new Application($kernel);
$container  = $application->getKernel()->getContainer();

$input      = new \Symfony\Component\Console\Input\ArgvInput([]);
$output     = new \Symfony\Component\Console\Output\ConsoleOutput();
$question   = new \Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper();
$em         = $container->get('doctrine.orm.entity_manager');
$elastic    = $container->get('core.service.elastic_search');

$callingstationids = [
    "00-08-22-FA-68-FC",
    "00-0A-F5-03-72-FC",
    "00-0A-F5-9F-14-F0",
    "00-16-98-10-C1-FD",
    "00-16-98-1C-48-D4",
    "00-16-98-1C-76-95",
    "00-16-98-1D-25-D9",
    "00-16-98-1F-4C-09",
    "00-16-98-F8-DB-04",
    "00-27-15-0C-AC-0E",
    "00-34-DA-1C-DC-63",
    "00-34-DA-21-DC-6C",
    "00-3D-E8-B3-FB-C3",
    "00-56-CD-87-92-55",
    "00-57-C1-EA-60-9A",
    "00-57-C1-EB-4B-76",
    "00-57-C1-EC-68-3B",
    "00-57-C1-EC-C5-33",
    "00-57-C1-ED-11-45",
    "00-6D-52-2D-A4-19",
    "00-DB-70-AE-18-50",
    "00-F4-6F-F4-0C-75",
    "00-F7-6F-9F-C9-A2",
    "00-F7-6F-B4-94-16",
    "04-1B-6D-AC-23-0B",
    "04-92-26-52-C2-91",
    "04-92-26-B9-60-20",
    "04-92-26-BA-3D-E8",
    "04-92-26-BC-BE-3F",
    "04-B1-67-18-90-96",
    "04-B1-67-50-36-B0",
    "04-D3-95-E4-4F-D4",
    "04-D3-95-E9-00-0A",
    "04-D3-95-EB-53-15",
    "04-D3-95-F0-DE-73",
    "04-D4-C4-28-2E-96",
    "04-D4-C4-A0-42-DF",
    "04-D6-AA-1F-90-83",
    "04-D6-AA-CF-8C-3C",
    "04-D9-F5-4B-FE-C3",
    "08-8C-2C-4A-79-94",
    "08-8C-2C-58-17-90",
    "08-8C-2C-AB-DC-19",
    "08-8C-2C-AE-43-59",
    "08-8C-2C-B2-82-E9",
    "08-8C-2C-BE-13-53",
    "08-8C-2C-F0-91-90",
    "08-8C-2C-F5-17-BE",
    "08-8C-2C-F6-75-EE",
    "08-8C-2C-FB-14-74",
    "08-C5-E1-8E-44-D9",
    "08-C5-E1-EC-1C-77",
    "08-CC-27-02-84-58",
    "08-CC-27-07-77-E8",
    "08-CC-27-74-5D-0A",
    "08-CC-27-74-C8-10",
    "08-CC-27-77-0F-2C",
    "08-CC-27-78-F9-0A",
    "08-CC-27-7B-0F-74",
    "08-CC-27-81-69-EF",
    "08-CC-27-87-DA-D4",
    "08-CC-27-89-FC-16",
    "08-CC-27-95-2C-C1",
    "08-CC-27-95-32-60",
    "08-CC-27-E3-6E-AE",
    "08-CC-27-E5-05-9B",
    "08-D4-6A-F7-63-46",
    "08-D4-6A-F9-FA-62",
    "0C-9D-92-7B-11-AC",
    "0C-9D-92-7C-7C-DE",
    "0C-9D-92-7D-37-F5",
    "0C-CB-85-40-56-BA",
    "0C-CB-85-44-D3-BA",
    "0C-CB-85-6A-DA-72",
    "0C-CB-85-6C-B7-78",
    "0C-CB-85-85-28-C8",
    "0C-CB-85-86-15-CE",
    "0C-CB-85-87-C1-90",
    "0C-CB-85-8D-12-EE",
    "0C-CB-85-C1-AF-2D",
    "0C-CB-85-C1-B1-34",
    "0C-E0-DC-A1-F3-F3",
    "10-7B-44-81-FA-7E",
    "10-7B-44-84-83-A2",
    "10-94-BB-05-D6-A2",
    "10-F1-F2-94-FD-C8",
    "10-F1-F2-96-6D-5B",
    "10-F1-F2-96-87-31",
    "14-32-D1-15-2B-C6",
    "14-56-8E-5B-2E-C6",
    "14-9D-99-C1-5B-82",
    "14-A3-64-A4-8A-75",
    "18-01-F1-61-FE-12",
    "18-01-F1-82-18-24",
    "18-01-F1-88-1B-42",
    "18-01-F1-92-D6-D8",
    "18-55-E3-0C-EB-50",
    "18-89-5B-02-A9-B0",
    "18-89-5B-41-C7-7D",
    "1C-56-FE-37-C6-2B",
    "1C-CC-D6-09-75-43",
    "1C-CC-D6-0A-59-7E",
    "1C-CC-D6-2E-39-6F",
    "1C-CC-D6-BD-F9-87",
    "1C-CC-D6-D4-4D-1B",
    "20-32-6C-19-6E-8F",
    "20-34-FB-B6-BB-60",
    "20-47-DA-7F-BF-69",
    "20-47-DA-EC-22-A2",
    "24-46-C8-00-20-5A",
    "24-46-C8-2C-D1-79",
    "24-46-C8-30-8B-9F",
    "24-46-C8-53-D7-3A",
    "24-46-C8-57-8E-7A",
    "24-46-C8-58-7E-CB",
    "24-46-C8-5D-5D-1E",
    "24-46-C8-B9-F1-24",
    "24-46-C8-DF-7C-E4",
    "24-46-C8-E7-81-BE",
    "24-C6-96-87-D0-1C",
    "24-F0-94-DD-61-B9",
    "28-16-7F-EA-1A-82",
    "28-5A-EB-BF-9F-D2",
    "28-83-35-14-B3-AB",
    "28-83-35-1F-B7-61",
    "28-83-35-2B-69-75",
    "28-83-35-33-4A-07",
    "28-83-35-43-C2-43",
    "28-83-35-43-CA-7D",
    "28-83-35-57-1D-C5",
    "28-83-35-66-22-57",
    "28-83-35-C2-8E-07",
    "28-83-35-C5-29-FB",
    "28-83-35-CB-34-79",
    "28-83-35-D5-C1-F9",
    "28-83-35-DE-82-05",
    "28-83-35-DF-4D-91",
    "28-83-35-E8-AB-3B",
    "28-83-35-EB-DA-4B",
    "28-83-35-F9-97-99",
    "2C-AE-2B-05-08-FF",
    "2C-F0-A2-E5-83-F9",
    "2C-FD-A1-64-B4-75",
    "2C-FD-A1-64-C2-4E",
    "30-10-E4-4F-C0-28",
    "30-4B-07-0D-CE-1B",
    "30-4B-07-16-BD-76",
    "30-4B-07-19-6D-36",
    "30-4B-07-37-CD-27",
    "30-4B-07-8E-8F-3B",
    "30-4B-07-C2-EE-A6",
    "30-4B-07-CD-F3-6F",
    "30-4B-07-D0-15-06",
    "30-A8-DB-E9-04-7C",
    "30-CB-F8-06-B4-69",
    "30-CB-F8-1B-DA-87",
    "30-CB-F8-3C-18-2D",
    "30-CB-F8-78-F0-73",
    "30-CB-F8-A8-D6-1A",
    "30-CB-F8-A9-C4-A8",
    "30-CB-F8-AD-1E-20",
    "30-CB-F8-BE-3F-22",
    "30-CB-F8-C0-D7-96",
    "32-3E-DF-4C-5C-6A",
    "34-97-F6-AA-4D-B9",
    "34-FC-EF-B1-0E-0E",
    "38-30-F9-3D-6A-35",
    "38-30-F9-3D-F6-59",
    "38-30-F9-69-AA-98",
    "38-30-F9-6D-29-3B",
    "38-30-F9-6E-10-5F",
    "38-30-F9-6F-16-B8",
    "38-80-DF-05-EA-06",
    "38-80-DF-35-56-56",
    "38-80-DF-37-BF-2D",
    "38-80-DF-3C-B5-BC",
    "38-80-DF-3C-D5-18",
    "38-80-DF-45-BE-99",
    "38-80-DF-6F-EA-F5",
    "38-80-DF-72-66-47",
    "38-80-DF-AA-CC-F5",
    "38-80-DF-AE-8E-3F",
    "38-80-DF-AE-AB-79",
    "38-80-DF-AF-56-4C",
    "38-80-DF-D7-FC-AE",
    "38-80-DF-DD-9C-F6",
    "38-80-DF-DE-29-4E",
    "38-80-DF-DE-69-BF",
    "38-80-DF-DE-88-BE",
    "38-80-DF-DF-A3-BD",
    "38-80-DF-E1-E6-A2",
    "38-80-DF-E3-62-0A",
    "38-9A-F6-05-EA-B9",
    "38-9A-F6-08-DB-A7",
    "38-9A-F6-13-C4-61",
    "38-9A-F6-18-56-ED",
    "38-9A-F6-1D-3B-43",
    "38-9A-F6-1D-3B-5D",
    "38-9A-F6-23-2C-85",
    "38-9A-F6-26-35-75",
    "38-9A-F6-33-55-79",
    "38-9A-F6-33-A6-D5",
    "38-9A-F6-36-61-03",
    "38-9A-F6-39-F7-2B",
    "38-9A-F6-3A-CF-87",
    "38-9A-F6-3E-43-07",
    "38-9A-F6-3E-6F-E7",
    "38-9A-F6-3E-84-1B",
    "38-9A-F6-40-29-E3",
    "38-9A-F6-42-42-C7",
    "38-9A-F6-42-E4-3B",
    "38-9A-F6-45-69-79",
    "38-9A-F6-46-2C-55",
    "38-9A-F6-4A-E5-BF",
    "38-9A-F6-50-E3-2F",
    "38-9A-F6-54-20-C5",
    "38-9A-F6-54-50-D5",
    "38-9A-F6-54-F7-FB",
    "38-9A-F6-5A-CC-A1",
    "38-9A-F6-67-C4-A7",
    "38-9A-F6-6E-0A-AF",
    "38-9A-F6-92-13-51",
    "38-9A-F6-92-48-15",
    "38-9A-F6-92-62-59",
    "38-9A-F6-A4-13-13",
    "38-9A-F6-AB-40-1F",
    "38-9A-F6-AD-20-85",
    "38-9A-F6-B4-B0-2D",
    "38-9A-F6-C1-A3-4B",
    "38-9A-F6-C6-D9-B5",
    "38-9A-F6-CE-D5-CB",
    "38-9A-F6-D2-CB-33",
    "38-9A-F6-D6-1D-CD",
    "38-9A-F6-D9-7B-C5",
    "38-9A-F6-DD-FA-67",
    "38-9A-F6-E2-D8-8D",
    "38-9A-F6-F6-0A-B3",
    "38-CA-DA-DA-EA-FB",
    "38-D4-0B-BC-AB-3A",
    "3C-BB-FD-82-D8-CA",
    "3C-BB-FD-85-44-BC",
    "3C-BB-FD-88-F1-DC",
    "3C-BB-FD-8B-28-1E",
    "3C-BB-FD-8C-83-0E",
    "3C-BB-FD-8E-E1-DC",
    "40-33-1A-E5-94-32",
    "40-45-DA-21-7D-F0",
    "40-83-1D-10-69-C3",
    "40-88-05-02-EF-8D",
    "40-88-05-11-4A-27",
    "40-88-05-55-92-C2",
    "40-88-05-61-97-19",
    "40-88-05-93-D0-56",
    "40-B0-76-1D-59-E7",
    "44-18-FD-E7-1D-4A",
    "44-65-60-01-76-8D",
    "44-74-6C-ED-41-AF",
    "44-91-60-56-B0-D7",
    "44-91-60-85-D2-2E",
    "44-D3-AD-80-EE-D5",
    "48-2C-A0-B7-55-61",
    "48-2C-A0-BA-FC-EC",
    "48-2C-A0-F9-D7-07",
    "48-49-C7-14-71-1C",
    "48-49-C7-17-78-B6",
    "48-49-C7-21-90-24",
    "48-49-C7-24-EC-D6",
    "48-49-C7-29-A3-3C",
    "48-49-C7-39-97-DA",
    "48-49-C7-3D-96-6E",
    "48-49-C7-43-68-02",
    "48-49-C7-4D-62-88",
    "48-49-C7-5D-5A-00",
    "48-49-C7-69-6D-9A",
    "48-49-C7-6E-EC-06",
    "48-49-C7-76-9A-F2",
    "48-49-C7-80-8B-02",
    "48-49-C7-82-57-D8",
    "48-49-C7-86-6C-06",
    "48-49-C7-94-FF-42",
    "48-49-C7-A4-81-8A",
    "48-49-C7-EA-4B-50",
    "48-49-C7-EC-C6-32",
    "48-49-C7-F7-23-08",
    "48-51-69-00-59-FE",
    "48-51-69-00-5D-D0",
    "48-51-69-11-41-CC",
    "48-51-69-15-2E-4E",
    "48-51-69-1E-AB-74",
    "48-51-69-27-0B-C6",
    "48-51-69-27-B9-FC",
    "48-51-69-36-58-22",
    "48-51-69-3C-AE-7E",
    "48-51-69-4B-EF-12",
    "48-51-69-51-72-BA",
    "48-51-69-60-D2-8C",
    "48-51-69-6B-D8-9C",
    "48-60-5F-0C-9A-02",
    "48-60-5F-38-34-F6",
    "48-60-5F-38-DD-59",
    "48-60-5F-3A-FC-B8",
    "48-60-5F-3B-89-61",
    "48-60-5F-3D-3F-18",
    "48-60-5F-73-20-9D",
    "48-60-5F-73-44-23",
    "48-FD-A3-EB-4F-8C",
    "4C-49-E3-C4-C7-55",
    "4C-ED-FB-33-02-DB",
    "4C-ED-FB-B9-CB-9D",
    "50-92-B9-0E-7E-26",
    "50-92-B9-19-1B-3E",
    "50-92-B9-2C-1D-F8",
    "50-92-B9-32-F6-78",
    "50-92-B9-41-EF-C2",
    "50-92-B9-47-3C-90",
    "50-92-B9-4B-87-54",
    "50-92-B9-57-67-4E",
    "50-92-B9-57-D0-A6",
    "50-92-B9-59-E3-16",
    "50-92-B9-6D-C8-1C",
    "50-92-B9-73-B3-18",
    "50-92-B9-77-53-5C",
    "50-92-B9-99-23-48",
    "50-92-B9-A0-B3-7C",
    "50-92-B9-A2-69-EE",
    "50-92-B9-B3-7F-00",
    "50-92-B9-D3-FD-54",
    "50-92-B9-F4-E8-88",
    "50-92-B9-F5-0E-1A",
    "50-92-B9-FE-BA-1E",
    "50-92-B9-FE-BC-2C",
    "50-A6-7F-4B-08-C2",
    "50-A6-7F-83-C9-7B",
    "54-99-63-BE-FB-2F",
    "54-FC-F0-22-99-D3",
    "56-4A-42-A2-25-5F",
    "58-20-59-07-EF-58",
    "58-20-59-7B-13-CD",
    "58-3F-54-FB-9C-A0",
    "58-40-4E-C2-EC-9F",
    "58-40-4E-D4-7E-F4",
    "58-6B-14-99-98-27",
    "58-6B-14-A7-5C-1B",
    "58-7F-57-7A-38-A5",
    "58-D9-C3-12-AC-56",
    "58-D9-C3-14-1C-1E",
    "58-D9-C3-17-B7-5B",
    "58-D9-C3-42-65-B2",
    "58-D9-C3-46-F8-90",
    "58-D9-C3-48-10-26",
    "58-D9-C3-7E-36-93",
    "58-D9-C3-86-BE-DF",
    "58-D9-C3-8E-5F-B0",
    "58-D9-C3-96-88-15",
    "58-D9-C3-97-92-80",
    "58-D9-C3-C6-E6-B1",
    "58-D9-C3-C7-12-A6",
    "58-D9-C3-C7-1A-3B",
    "58-D9-C3-CC-9B-24",
    "58-D9-C3-CD-30-16",
    "58-D9-C3-D3-38-D4",
    "58-D9-C3-D4-4A-EE",
    "58-D9-C3-D5-45-F8",
    "58-E2-8F-B9-59-1F",
    "58-E2-8F-E3-2C-76",
    "58-E6-BA-20-AC-6E",
    "5C-51-88-38-2A-77",
    "5C-51-88-EC-FB-F4",
    "5C-70-A3-52-7D-80",
    "5C-AF-06-19-0C-FD",
    "5C-AF-06-1A-E3-34",
    "5C-AF-06-2E-F8-49",
    "5C-AF-06-78-C0-4F",
    "60-03-08-2E-DB-4E",
    "60-1D-91-00-98-3A",
    "60-1D-91-03-79-6E",
    "60-1D-91-05-68-E6",
    "60-1D-91-07-28-01",
    "60-1D-91-09-91-A1",
    "60-1D-91-0B-95-1D",
    "60-1D-91-0D-DD-F6",
    "60-1D-91-0E-98-65",
    "60-1D-91-7F-75-48",
    "60-1D-91-84-63-7F",
    "60-1D-91-84-D0-F3",
    "60-1D-91-A1-DD-C8",
    "60-1D-91-A9-C6-4D",
    "60-30-D4-4E-1A-40",
    "60-30-D4-53-4F-44",
    "60-45-CB-97-78-E4",
    "60-AB-67-80-5F-5B",
    "60-AB-67-82-E5-E0",
    "60-AB-67-86-96-C3",
    "60-AB-67-95-DA-D9",
    "60-AB-67-97-8F-51",
    "60-AB-67-9E-0D-58",
    "60-AB-67-C9-80-CA",
    "60-AB-67-D6-B6-19",
    "60-AB-67-F3-D9-03",
    "60-AB-67-F4-AA-9D",
    "60-AB-67-FC-8B-CE",
    "64-C2-DE-14-B0-B4",
    "64-C2-DE-14-B0-CB",
    "64-C2-DE-3A-16-96",
    "64-C2-DE-3C-B0-7F",
    "64-C2-DE-63-CC-23",
    "64-C2-DE-8F-51-B6",
    "64-C2-DE-8F-99-90",
    "64-C2-DE-91-99-08",
    "64-C2-DE-92-5F-76",
    "64-C2-DE-93-25-F4",
    "64-C2-DE-95-4D-9C",
    "64-DB-43-45-5C-30",
    "68-7D-6B-18-ED-6F",
    "68-7D-6B-1A-F5-81",
    "68-7D-6B-1E-26-9B",
    "68-7D-6B-3A-46-CF",
    "68-7D-6B-3A-F7-7D",
    "68-7D-6B-3D-61-55",
    "68-7D-6B-3E-86-45",
    "68-7D-6B-45-36-79",
    "68-7D-6B-47-62-67",
    "68-7D-6B-54-36-ED",
    "68-7D-6B-63-5A-D7",
    "68-7D-6B-6E-43-61",
    "68-C4-4D-23-8E-06",
    "68-C4-4D-58-41-42",
    "68-C4-4D-C7-E9-B7",
    "68-C4-4D-E5-30-15",
    "68-FB-7E-26-26-4E",
    "70-14-A6-63-A5-E3",
    "70-3A-51-1E-BC-C2",
    "70-3A-51-30-0A-B9",
    "70-3A-51-8D-C7-4B",
    "70-BB-E9-A7-9E-C5",
    "70-BB-E9-CF-56-B7",
    "70-BB-E9-D5-A9-1B",
    "70-BB-E9-F8-3E-19",
    "70-BB-E9-FB-77-FB",
    "70-EC-E4-75-71-38",
    "70-EF-00-4C-48-A2",
    "70-F0-87-CE-16-46",
    "70-FD-46-02-DA-76",
    "70-FD-46-06-06-D6",
    "70-FD-46-09-D3-DC",
    "70-FD-46-0B-93-1C",
    "70-FD-46-0C-DE-50",
    "70-FD-46-11-3E-18",
    "70-FD-46-12-6C-24",
    "70-FD-46-1D-31-94",
    "70-FD-46-37-66-FC",
    "70-FD-46-37-B8-9C",
    "70-FD-46-3C-A3-22",
    "70-FD-46-42-30-F0",
    "70-FD-46-5C-84-66",
    "70-FD-46-99-0E-3A",
    "70-FD-46-B7-43-48",
    "70-FD-46-C7-09-E0",
    "70-FD-46-C7-F7-34",
    "70-FD-46-CC-62-1C",
    "70-FD-46-D4-85-6C",
    "70-FD-46-D5-6B-3C",
    "70-FD-46-D5-71-9A",
    "70-FD-46-D7-DF-A2",
    "70-FD-46-DC-AA-F0",
    "70-FD-46-DC-C6-52",
    "70-FD-46-DF-5E-F6",
    "70-FD-46-E4-9E-50",
    "70-FD-46-F2-09-06",
    "70-FD-46-F4-6C-B2",
    "70-FD-46-F4-6D-D8",
    "70-FD-46-F4-72-E4",
    "70-FD-46-FC-CB-EE",
    "74-40-BB-F3-12-95",
    "74-B5-87-20-DF-DB",
    "74-E5-43-FC-77-DC",
    "74-E5-43-FE-9F-35",
    "74-E5-43-FE-A1-1C",
    "74-E5-43-FE-A1-1F",
    "74-E5-43-FE-A1-CE",
    "78-67-D7-20-F5-80",
    "78-88-6D-38-A0-1E",
    "7C-03-5E-96-02-D5",
    "7C-2E-DD-6C-B8-78",
    "7C-46-85-26-12-14",
    "7C-46-85-26-20-FC",
    "7C-8B-B5-02-5E-1D",
    "7C-8B-B5-08-BA-1F",
    "7C-8B-B5-0A-04-2D",
    "7C-8B-B5-11-47-AB",
    "7C-8B-B5-1E-BA-6F",
    "7C-8B-B5-20-A1-F7",
    "7C-8B-B5-21-0A-9B",
    "7C-8B-B5-29-16-51",
    "7C-8B-B5-2A-1F-5D",
    "7C-8B-B5-37-1F-51",
    "7C-8B-B5-46-A6-E5",
    "7C-8B-B5-4C-13-7D",
    "7C-8B-B5-53-1C-13",
    "7C-8B-B5-53-DE-35",
    "7C-8B-B5-55-85-DB",
    "7C-8B-B5-55-A9-D3",
    "7C-8B-B5-55-BB-CF",
    "7C-8B-B5-56-22-8F",
    "7C-8B-B5-5F-12-E5",
    "7C-8B-B5-60-9F-23",
    "7C-8B-B5-62-32-A9",
    "7C-8B-B5-66-C7-D3",
    "7C-8B-B5-67-CC-DD",
    "7C-8B-B5-6B-6E-FB",
    "7C-8B-B5-6D-CC-4F",
    "7C-8B-B5-6D-FE-35",
    "7C-8B-B5-70-85-BB",
    "7C-8B-B5-77-F1-DB",
    "7C-8B-B5-7A-6F-1D",
    "7C-8B-B5-7C-1F-AD",
    "7C-8B-B5-7E-FE-CF",
    "7C-8B-B5-8A-B8-9D",
    "7C-8B-B5-8B-08-AF",
    "7C-8B-B5-95-30-A5",
    "7C-8B-B5-9C-9A-5B",
    "7C-8B-B5-A2-12-17",
    "7C-8B-B5-AC-22-F1",
    "7C-8B-B5-B1-0B-BD",
    "7C-8B-B5-B2-04-21",
    "7C-8B-B5-BB-A3-BD",
    "7C-8B-B5-BE-DD-C1",
    "7C-8B-B5-C3-20-E5",
    "7C-8B-B5-C6-2A-CD",
    "7C-8B-B5-C8-52-F9",
    "7C-8B-B5-CF-26-3B",
    "7C-8B-B5-D0-D8-B5",
    "7C-8B-B5-D9-88-75",
    "7C-8B-B5-DC-E1-B1",
    "7C-8B-B5-DE-7C-D1",
    "7C-8B-B5-E2-C7-FF",
    "7C-8B-B5-EF-92-5D",
    "7C-8B-B5-F0-A6-41",
    "7C-8B-B5-F9-5B-27",
    "7C-8B-B5-FE-60-1D",
    "7C-8B-B5-FE-E3-CF",
    "7C-D6-61-38-23-B8",
    "7C-D6-61-6E-84-89",
    "7C-D6-61-C5-19-C9",
    "7C-F3-1B-A1-5F-D6",
    "7C-F3-1B-A1-63-0C",
    "7C-F3-1B-A3-37-00",
    "7C-F3-1B-A4-BC-21",
    "7E-C2-F1-78-40-2A",
    "80-2B-F9-FE-98-41",
    "80-58-F8-03-D1-33",
    "80-58-F8-08-4B-7B",
    "80-58-F8-3A-21-22",
    "80-58-F8-40-FB-4D",
    "80-58-F8-42-4F-01",
    "80-58-F8-43-AB-1E",
    "80-58-F8-6D-64-4C",
    "80-58-F8-72-39-9F",
    "80-58-F8-73-CC-34",
    "80-58-F8-75-E7-2D",
    "80-58-F8-A9-AC-BC",
    "80-58-F8-B2-50-10",
    "80-58-F8-E6-B4-BF",
    "80-58-F8-E9-56-AB",
    "80-58-F8-F0-D2-9E",
    "80-5A-04-AF-DB-F7",
    "80-5A-04-F7-95-DB",
    "80-65-6D-4E-C1-B7",
    "80-82-23-75-37-25",
    "80-EA-96-AE-51-6C",
    "84-10-0D-03-97-EF",
    "84-10-0D-BC-16-38",
    "84-11-9E-D0-41-86",
    "88-36-5F-E9-65-1C",
    "88-36-5F-EB-C1-4C",
    "88-36-5F-EC-56-6E",
    "88-66-A5-C6-1E-87",
    "88-79-7E-6D-9E-2A",
    "88-79-7E-91-DE-24",
    "88-79-7E-9F-28-58",
    "88-79-7E-B1-AD-B5",
    "88-79-7E-DF-FC-8A",
    "88-A9-B7-24-C1-C8",
    "88-B4-A6-50-29-A7",
    "88-B4-A6-50-97-45",
    "88-B4-A6-62-8A-08",
    "88-B4-A6-8C-87-AE",
    "88-B4-A6-8C-8F-70",
    "88-B4-A6-A1-76-5A",
    "88-B4-A6-AA-12-1A",
    "88-B4-A6-D7-D8-AF",
    "88-B4-A6-EA-9F-A4",
    "88-B4-A6-EA-ED-77",
    "88-B4-A6-EE-EC-A7",
    "88-B4-A6-EF-F9-B7",
    "88-B4-A6-F3-46-7C",
    "88-D7-F6-AD-CA-C8",
    "8C-45-00-BD-5B-E5",
    "8C-B8-4A-62-AB-E2",
    "8C-E5-C0-7B-D6-F4",
    "8C-E5-C0-87-55-A6",
    "8C-E5-C0-BB-E6-A0",
    "8C-E5-C0-C5-DA-84",
    "8C-F1-12-1C-41-01",
    "8C-F1-12-45-9A-1C",
    "8C-F1-12-4B-A7-48",
    "8C-F1-12-7A-B4-D6",
    "8C-F1-12-7F-3D-A6",
    "8C-F1-12-81-70-BD",
    "8C-F1-12-83-08-AE",
    "8C-F1-12-87-21-76",
    "8C-F1-12-8A-E1-0D",
    "8C-F1-12-93-EB-CF",
    "8C-F1-12-C3-1C-AF",
    "8C-F5-A3-3C-C7-C9",
    "8C-F5-A3-D7-D9-68",
    "90-60-F1-01-80-A6",
    "90-73-5A-17-FE-34",
    "90-73-5A-24-04-A6",
    "90-73-5A-5D-26-FA",
    "90-73-5A-5D-61-65",
    "90-73-5A-64-71-CF",
    "90-73-5A-68-96-40",
    "90-73-5A-6E-4C-6A",
    "90-73-5A-96-F2-31",
    "90-73-5A-9A-DC-15",
    "90-73-5A-9C-A2-FE",
    "90-73-5A-A7-8A-99",
    "90-73-5A-A7-A1-DC",
    "90-73-5A-B1-80-3C",
    "90-73-5A-E1-B7-F5",
    "90-73-5A-E5-B6-BC",
    "90-78-B2-0A-D1-15",
    "90-78-B2-43-C2-F6",
    "94-0C-98-9D-AC-65",
    "98-39-8E-09-0D-1B",
    "98-39-8E-54-30-F1",
    "98-39-8E-6A-49-67",
    "98-39-8E-6E-AA-BB",
    "98-39-8E-76-B4-A1",
    "98-39-8E-77-40-91",
    "98-39-8E-84-90-67",
    "98-39-8E-87-D4-D1",
    "98-39-8E-93-0D-97",
    "98-39-8E-9D-EB-2B",
    "98-39-8E-A4-42-E3",
    "98-39-8E-B9-F4-95",
    "98-39-8E-BF-92-41",
    "98-39-8E-D5-5D-F7",
    "98-39-8E-D5-F8-73",
    "98-39-8E-DA-FB-21",
    "98-39-8E-EE-3D-85",
    "98-B8-BA-99-F1-D1",
    "98-B8-BA-9C-B8-4D",
    "9C-2E-A1-1E-9E-A1",
    "9C-5C-8E-5B-6F-A9",
    "9C-64-8B-29-D8-29",
    "9C-64-8B-50-62-0F",
    "9C-99-A0-D0-4F-B4",
    "9C-E6-5E-3A-06-A4",
    "A0-39-F7-9A-50-70",
    "A0-4E-A7-3D-F4-8A",
    "A4-45-19-17-E7-EF",
    "A4-50-46-27-80-EF",
    "A4-50-46-3A-E6-6D",
    "A4-50-46-40-88-BD",
    "A4-50-46-6D-D7-7D",
    "A4-50-46-E2-E5-1C",
    "A4-50-46-EC-1C-F3",
    "A4-70-D6-1B-F1-B3",
    "A4-70-D6-54-1D-A2",
    "A4-70-D6-60-C2-CD",
    "A4-70-D6-92-39-10",
    "A4-E9-75-5A-15-07",
    "A8-16-D0-06-66-63",
    "A8-16-D0-2A-33-27",
    "A8-16-D0-2C-B3-B3",
    "A8-16-D0-39-BB-25",
    "A8-16-D0-4C-7C-39",
    "A8-16-D0-57-38-51",
    "A8-16-D0-6D-03-C3",
    "A8-16-D0-71-1B-21",
    "A8-16-D0-83-91-A7",
    "A8-16-D0-86-73-8F",
    "A8-16-D0-8C-F4-AD",
    "A8-16-D0-8D-C6-EF",
    "A8-16-D0-91-89-F7",
    "A8-16-D0-95-04-3B",
    "A8-16-D0-99-29-AD",
    "A8-16-D0-A2-54-15",
    "A8-16-D0-AD-08-D1",
    "A8-16-D0-D0-B6-A5",
    "A8-16-D0-DD-45-C9",
    "A8-16-D0-E6-52-A1",
    "A8-16-D0-E6-57-AF",
    "A8-16-D0-FA-8E-13",
    "A8-16-D0-FB-39-B7",
    "A8-5B-78-46-C9-30",
    "A8-96-75-12-01-5D",
    "A8-96-75-13-3D-CE",
    "A8-96-75-13-48-90",
    "A8-96-75-17-35-3B",
    "A8-96-75-3E-BB-19",
    "A8-96-75-3F-78-58",
    "A8-96-75-4C-76-43",
    "A8-96-75-6E-BA-19",
    "A8-96-75-7F-40-C9",
    "A8-96-75-A6-4B-9B",
    "A8-96-75-B8-00-B2",
    "A8-96-75-D9-33-C9",
    "A8-96-75-DD-96-59",
    "A8-96-75-DF-A5-0F",
    "A8-9C-ED-09-FD-2B",
    "A8-9C-ED-3E-4A-A7",
    "A8-9C-ED-C4-4E-2C",
    "A8-B8-6E-68-B7-82",
    "A8-BE-27-BC-E5-AE",
    "A8-DB-03-0D-EB-6A",
    "A8-DB-03-D9-F0-33",
    "AC-0D-1B-DF-BF-1A",
    "AC-5F-3E-F0-88-6C",
    "AC-5F-3E-F2-2D-DC",
    "AC-E4-B5-AE-48-97",
    "AC-F6-F7-C4-C6-84",
    "AC-F6-F7-C5-68-FC",
    "AC-F6-F7-C5-9B-54",
    "AC-F6-F7-C7-7E-A4",
    "AC-F6-F7-C9-FC-CE",
    "AC-F6-F7-CB-42-57",
    "B0-47-BF-BF-A4-B1",
    "B0-47-BF-D1-6B-4F",
    "B0-70-2D-2F-D8-14",
    "B4-9C-DF-12-1A-37",
    "B4-C4-FC-4F-EA-7F",
    "B4-C4-FC-55-A7-43",
    "B4-C4-FC-6D-AA-AE",
    "B4-EF-FA-B8-60-32",
    "B4-F1-DA-B0-F4-CD",
    "B4-F1-DA-F8-F8-C1",
    "B4-F1-DA-FB-17-64",
    "B4-F1-DA-FE-49-79",
    "B4-F7-A1-A2-0F-EC",
    "B4-F7-A1-A2-52-8A",
    "B8-1D-AA-E3-90-D2",
    "BC-6C-21-2E-70-2A",
    "BC-98-DF-23-14-0E",
    "BC-98-DF-5A-46-DE",
    "BC-98-DF-B0-31-BD",
    "BC-98-DF-DE-EE-61",
    "BC-98-DF-ED-C6-61",
    "BC-98-DF-F2-53-66",
    "BC-E1-43-38-35-DF",
    "BC-FF-EB-22-43-A8",
    "BC-FF-EB-23-35-37",
    "BC-FF-EB-3C-02-E9",
    "BC-FF-EB-54-EB-EB",
    "BC-FF-EB-59-4C-0E",
    "BC-FF-EB-5B-64-AE",
    "BC-FF-EB-5F-1E-78",
    "C0-11-73-C8-6B-BE",
    "C0-8C-71-2D-71-2B",
    "C0-8C-71-34-07-C8",
    "C0-8C-71-34-E7-97",
    "C0-8C-71-67-D8-75",
    "C0-8C-71-6B-E2-BB",
    "C0-8C-71-75-77-7C",
    "C0-8C-71-76-69-45",
    "C0-8C-71-99-92-09",
    "C0-8C-71-B5-2D-88",
    "C0-8C-71-C3-87-9B",
    "C0-B6-58-4D-B4-11",
    "C0-D0-12-BD-E7-0E",
    "C4-61-8B-C3-62-36",
    "C4-98-80-9E-AC-7E",
    "C4-9A-02-03-54-D6",
    "C6-2F-AC-6A-CD-7A",
    "C8-3D-DC-AD-6D-BC",
    "C8-D0-83-3D-5B-D6",
    "CC-44-63-0D-A0-7D",
    "CC-61-E5-35-AA-ED",
    "CC-61-E5-6F-71-16",
    "CC-79-4A-48-E5-11",
    "D0-04-01-4E-C4-47",
    "D0-04-01-86-DE-B1",
    "D0-04-01-90-55-44",
    "D0-04-01-93-10-A1",
    "D0-13-FD-0B-53-E7",
    "D0-13-FD-0D-6E-5C",
    "D0-2B-20-C4-2A-11",
    "D0-31-69-07-39-C4",
    "D0-77-14-15-E4-5B",
    "D0-77-14-6C-D8-2E",
    "D0-77-14-6D-08-5E",
    "D0-77-14-DF-8E-DA",
    "D0-81-7A-3C-6B-2E",
    "D0-81-7A-45-EA-78",
    "D0-9C-7A-AD-45-18",
    "D0-9C-7A-BA-95-98",
    "D0-9C-7A-EC-40-45",
    "D0-C5-F3-12-7A-72",
    "D0-D2-B0-60-E4-8C",
    "D0-F8-8C-2A-F0-1B",
    "D0-F8-8C-2B-88-E1",
    "D0-F8-8C-45-6D-CD",
    "D4-63-C6-12-3B-42",
    "D4-63-C6-13-B5-AA",
    "D4-63-C6-14-51-62",
    "D4-63-C6-15-21-D4",
    "D4-63-C6-20-51-57",
    "D4-63-C6-51-6D-91",
    "D4-63-C6-7E-9B-D5",
    "D4-63-C6-87-48-2A",
    "D4-63-C6-A1-38-F1",
    "D4-63-C6-A3-3D-8D",
    "D4-63-C6-A9-CA-70",
    "D4-63-C6-B5-C8-66",
    "D4-63-C6-B7-2C-B8",
    "D4-63-C6-B8-E3-E7",
    "D4-63-C6-C7-F3-78",
    "D4-63-C6-C9-53-DF",
    "D4-63-C6-CD-77-C2",
    "D4-63-C6-CD-8F-0B",
    "D4-63-C6-CE-A6-93",
    "D4-A3-3D-D6-D6-73",
    "D4-C9-4B-29-A9-88",
    "D4-C9-4B-2B-FB-37",
    "D4-C9-4B-32-BA-2F",
    "D4-C9-4B-34-A9-29",
    "D4-C9-4B-8E-5F-BC",
    "D4-C9-4B-92-93-DE",
    "D4-C9-4B-98-DB-A4",
    "D4-C9-4B-9A-30-AE",
    "D4-C9-4B-B6-FD-D0",
    "D4-C9-4B-E8-89-A0",
    "D8-08-31-00-94-8A",
    "D8-C4-6A-51-54-D6",
    "D8-CE-3A-42-4F-49",
    "D8-CE-3A-4E-8A-FD",
    "D8-CE-3A-8F-13-B5",
    "D8-CE-3A-E8-A7-D5",
    "DC-08-0F-7C-EF-D3",
    "DC-08-0F-85-3D-2D",
    "DC-08-0F-8A-16-0E",
    "DC-0B-34-D1-57-D4",
    "DC-35-F1-28-F2-39",
    "DC-56-E7-90-A7-D9",
    "DC-BF-E9-08-A3-5E",
    "DC-BF-E9-08-B9-1E",
    "DC-BF-E9-0A-2E-E8",
    "DC-BF-E9-0A-59-8A",
    "DC-BF-E9-0E-44-29",
    "DC-BF-E9-11-A5-B5",
    "DC-BF-E9-12-02-58",
    "DC-BF-E9-14-A7-A1",
    "DC-BF-E9-16-96-56",
    "DC-BF-E9-38-81-5A",
    "DC-BF-E9-42-5F-43",
    "DC-BF-E9-42-E6-8E",
    "DC-BF-E9-43-57-0B",
    "DC-BF-E9-46-55-91",
    "DC-BF-E9-47-B1-F0",
    "DC-BF-E9-5C-1C-0F",
    "DC-BF-E9-8B-92-3A",
    "DC-BF-E9-8D-50-E6",
    "DC-BF-E9-9D-F9-FB",
    "DC-BF-E9-C4-F4-30",
    "DC-BF-E9-C5-C9-CF",
    "DC-BF-E9-C5-E1-54",
    "DC-BF-E9-C9-8F-D1",
    "DC-BF-E9-CA-91-CD",
    "DC-BF-E9-CF-EA-62",
    "E0-5F-45-63-E9-72",
    "E0-DC-FF-0D-FB-97",
    "E4-90-7E-7C-FC-C1",
    "E4-B2-FB-49-6A-B9",
    "E8-50-8B-CD-F3-E2",
    "E8-5A-8B-28-4D-CE",
    "E8-5A-8B-B1-B9-C9",
    "E8-5A-8B-C1-6F-95",
    "E8-91-20-37-0A-5C",
    "E8-91-20-CA-99-7C",
    "E8-B4-C8-2A-7F-2A",
    "E8-FB-E9-3E-72-98",
    "EC-9B-F3-E2-34-90",
    "F0-79-60-A2-D1-4E",
    "F0-98-9D-0C-A0-20",
    "F0-D7-AA-05-27-A2",
    "F0-D7-AA-08-FF-91",
    "F0-D7-AA-0B-2D-4F",
    "F0-D7-AA-0D-E7-B3",
    "F0-D7-AA-0E-CB-96",
    "F0-D7-AA-10-BF-A0",
    "F0-D7-AA-14-40-B6",
    "F0-D7-AA-1A-DD-78",
    "F0-D7-AA-1B-E9-4B",
    "F0-D7-AA-20-2A-2D",
    "F0-D7-AA-26-A8-D6",
    "F0-D7-AA-4A-AC-EB",
    "F0-D7-AA-51-F9-5E",
    "F0-D7-AA-57-E9-10",
    "F0-D7-AA-99-F1-67",
    "F0-D7-AA-9B-3C-9A",
    "F0-D7-AA-A6-0D-7B",
    "F0-D7-AA-A7-A4-F1",
    "F0-D7-AA-B1-AD-F0",
    "F0-D7-AA-B5-5F-6A",
    "F0-D7-AA-DB-78-64",
    "F0-D7-AA-E7-AC-21",
    "F0-D7-AA-E8-A5-58",
    "F2-2C-BE-79-B8-84",
    "F4-06-16-DA-F7-41",
    "F4-06-16-E4-07-9B",
    "F4-0E-22-80-F2-C4",
    "F4-0E-22-86-D2-08",
    "F4-60-E2-2D-B1-CA",
    "F4-60-E2-2F-65-F2",
    "F4-60-E2-B1-5D-56",
    "F4-60-E2-C4-F9-12",
    "F4-60-E2-C6-4D-BC",
    "F4-F1-E1-D5-1C-95",
    "F4-F5-24-10-BA-2F",
    "F4-F5-24-13-5A-8C",
    "F4-F5-24-1A-E2-8D",
    "F4-F5-24-22-AA-DA",
    "F4-F5-24-61-FE-4E",
    "F4-F5-24-A1-B6-35",
    "F4-F5-24-AD-5A-32",
    "F4-F5-24-B5-00-86",
    "F4-F5-24-B5-34-D7",
    "F4-F5-24-F5-F3-50",
    "F8-95-EA-2F-5C-71",
    "F8-95-EA-AA-1B-3B",
    "F8-A9-D0-12-ED-0C",
    "FC-19-10-5F-53-FC",
    "FC-64-3A-01-61-F4",
    "FC-64-3A-03-06-2E",
    "FC-64-3A-0D-69-92",
    "FC-64-3A-14-12-8A",
    "FC-64-3A-14-65-2A",
    "FC-64-3A-14-96-8C",
    "FC-64-3A-16-22-F6",
    "FC-64-3A-1A-63-CA",
    "FC-64-3A-1B-4B-22",
    "FC-64-3A-1C-42-66",
    "FC-64-3A-24-F1-DC",
    "FC-64-3A-3E-CF-56",
    "FC-64-3A-4B-A4-2C",
    "FC-64-3A-53-DF-88",
    "FC-64-3A-5B-63-8E",
    "FC-64-3A-66-78-66",
    "FC-64-3A-6E-BC-0C",
    "FC-64-3A-82-8B-6A",
    "FC-64-3A-83-62-72",
    "FC-64-3A-89-CB-2A",
    "FC-64-3A-8B-B9-CC",
    "FC-64-3A-92-BC-08",
    "FC-64-3A-9B-41-BC",
    "FC-64-3A-9E-F1-F6",
    "FC-64-3A-A7-E0-E2",
    "FC-64-3A-A9-B6-3C",
    "FC-64-3A-AC-8F-32",
    "FC-64-3A-B3-68-4A",
    "FC-64-3A-B4-9D-36",
    "FC-64-3A-C0-A9-1A",
    "FC-64-3A-C4-75-12",
    "FC-64-3A-C6-96-68",
    "FC-64-3A-CC-7D-36",
    "FC-64-3A-CC-9F-06",
    "FC-64-3A-CE-65-6C",
    "FC-64-3A-D6-DD-E0",
    "FC-64-3A-DB-43-E6",
    "FC-64-3A-E2-3C-E2",
    "FC-64-3A-E8-F3-4A",
    "FC-64-3A-EA-D6-3C",
    "FC-64-3A-F1-B3-EC",
    "FC-64-3A-F6-D3-CA"
];

$index = "wspot_2019_03,wspot_2019_04,wspot_2019_05,wspot_2019_06,wspot_2019_07,wspot_2019_08,wspot_2019_09,wspot_2019_10,wspot_2019_11,wspot_2019_12,wspot_2020_01,wspot_2020_02,wspot_2020_03";

$items = [];

foreach ($callingstationids as $callingstationid) {
    $body = [
        "size" => 1,
        "query" => [
        "bool" => [
            "must" => [
                [
                    "term" => [
                        "callingstationid" => $callingstationid
                    ]
                ]
              ]
            ]
        ]
    ];

    $result = $elastic->search('radacct', $body, $index);

    $clientId = "";

    if ($result['hits']['total'] > 0) {
        $clientId = $result['hits']['hits'][0]['_source']['client_id'];
    }

    $items[$callingstationid] = $clientId;
}

$i = 0;
foreach ($items as $key => $value) {
    $client = $em->getRepository("DomainBundle:Client")->findOneById($value);

    $i++;
    echo "$key, $value, {$client->getDomain()}\n";
    if ($i == 100) {
        echo "\n\n\n\n\n";
        $i = 0;
        sleep(10);
    }
}
