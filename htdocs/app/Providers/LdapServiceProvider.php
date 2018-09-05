<?php

namespace App\Providers;

use Log;
use Config;
use Illuminate\Support\ServiceProvider;

class LdapServiceProvider extends ServiceProvider
{

    private $groupList;
    private static $ldapConnectId = null;

    public function __construct()
    {
        if (is_null(self::$ldapConnectId))
            $this->connect();
    }

    public function error()
    {
        if (is_null(self::$ldapConnectId)) return;
        return ldap_error(self::$ldapConnectId);
    }

    public function connect()
    {
        if ($ldapconn = @ldap_connect(Config::get('ldap.host')))
        {
            @ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, intval(Config::get('ldap.version')));
            @ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
            self::$ldapConnectId = $ldapconn;
        }
        else
            Log::error("Connecting LDAP server failed.\n");
    }

    public function administrator() 
    {
		@ldap_bind(self::$ldapConnectId, Config::get('ldap.rootdn'), Config::get('ldap.rootpwd'));
    }
    
    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) return false;
    	$account = Config::get('ldap.authattr')."=".$username;
    	$base_dn = Config::get('ldap.authdn');
    	$auth_dn = "$account,$base_dn";
    	return @ldap_bind(self::$ldapConnectId, $auth_dn, $password);
    }

    public function userLogin($username, $password)
    {
        if (empty($username) || empty($password)) return false;
    	$base_dn = Config::get('ldap.userdn');
    	$user_dn = "$username,$base_dn";
    	return @ldap_bind(self::$ldapConnectId, $user_dn, $password);
    }

    public function schoolLogin($username, $password)
    {
        if (empty($username) || empty($password)) return false;
    	$base_dn = Config::get('ldap.rdn');
    	$sch_dn = "$username,$base_dn";
    	return @ldap_bind(self::$ldapConnectId, $sch_dn, $password);
    }

    public function checkIdno($idno)
    {
    	if (empty($idno)) return false;
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $idno);
		if ($resource) {
	    	return substr($idno,3);
		}
        return false;
    }

    public function checkSchoolAdmin($dc)
    {
		if (empty($dc)) return false;
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.rdn'), $dc, array("tpAdministrator"));
		if ($resource) {
	    	$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			if (!$entry) return false;
			return true;
		}
		return false;
    }

    public function checkAccount($username)
    {
        if (empty($username)) return false;
        $filter = "uid=".$username;
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.authdn'), $filter, array("cn"));
		if ($resource) {
	    	$entry = ldap_first_entry(self::$ldapConnectId, $resource);
	    	if (!$entry) return false;
	    	$id = ldap_get_values(self::$ldapConnectId, $entry, "cn");
	    	return $id[0];
		}
        return false;
    }

    public function checkEmail($email)
    {
        if (empty($email)) return false;
        $filter = "mail=".$email."*";
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $filter, array("cn"));
		if ($resource) {
	    	$entry = ldap_first_entry(self::$ldapConnectId, $resource);
	    	if (!$entry) return false;
	    	$id = ldap_get_values(self::$ldapConnectId, $entry, "cn");
	    	return $id[0];
		} 
        return false;
    }

    public function checkMobil($mobile)
    {
        if (empty($mobile)) return false;
        $filter = "mobile=".$mobile;
		$this->administrator();
		$resource = ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $filter, array("cn"));
		if ($resource) {
	    	$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
	    	if (!$entry) return false;
	    	$id = ldap_get_values(self::$ldapConnectId, $entry, "cn");
	    	return $id[0];
		} 
        return false;
    }

    public function accountAvailable($idno, $account)
    {
        if (empty($idno) || empty($account)) return;

        $filter = "(&(uid=".$account.")(!(".Config::get('ldap.userattr')."=".$idno.")))";
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $filter);
		if (ldap_first_entry(self::$ldapConnectId, $resource)) {
	    	return false;
		} else {
            return true;
		}
    }

    public function emailAvailable($idno, $mailaddr)
    {
        if (empty($idno) || empty($mailaddr)) return;

        $filter = "(&(mail=".$mailaddr.")(!(".Config::get('ldap.userattr')."=".$idno.")))";
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $filter);
		if (ldap_first_entry(self::$ldapConnectId, $resource)) {
	    	return false;
		} else {
            return true;
		}
    }

    public function mobileAvailable($idno, $mobile)
    {
        if (empty($idno) || empty($mobile)) return;

        $filter = "(&(mobile=".$mobile.")(!(".Config::get('ldap.userattr')."=".$idno.")))";
		$this->administrator();
		$resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.userdn'), $filter);
		if (ldap_first_entry(self::$ldapConnectId, $resource)) {
	    	return false;
		} else {
            return true;
		}
    }

    public function getOrgs($filter = '')
    {
		$schools = array();
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		if (empty($filter)) $filter = "objectClass=tpeduSchool";
		$resource = @ldap_search(self::$ldapConnectId, $base_dn, $filter, ['o', 'st', 'tpUniformNumbers', 'description']);
		$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
		if ($entry) {
		    do {
	    		$school = new \stdClass();
	    		foreach (['o', 'st', 'tpUniformNumbers', 'description'] as $field) {
					$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
					if ($value) $school->$field = $value[0];
	    		}
	    		$schools[] = $school;
		    } while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
		}
		return $schools;
    }

    public function getOrgEntry($identifier)
    {
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$identifier;
		$resource = @ldap_search(self::$ldapConnectId, $base_dn, $sch_rdn);
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }
    
    public function getOrgData($entry, $attr = '')
    {
		$fields = array();
		if (is_array($attr)) {
	    	$fields = $attr;
		} elseif ($attr == '') {
	    	$fields[] = 'o';
	    	$fields[] = 'businessCategory';
	    	$fields[] = 'st';
	    	$fields[] = 'description';
	    	$fields[] = 'facsimileTelephoneNumber';
	    	$fields[] = 'telephoneNumber';
	    	$fields[] = 'postalCode';
	    	$fields[] = 'street';
	    	$fields[] = 'postOfficeBox';
	    	$fields[] = 'wWWHomePage';
	    	$fields[] = 'tpUniformNumbers';
	    	$fields[] = 'tpIpv4';
	    	$fields[] = 'tpIpv6';
	    	$fields[] = 'tpAdministrator';
		} else {
	    	$fields[] = $attr;
		}
	
		$info = array();
        foreach ($fields as $field) {
    	    if ($field == 'ou') continue;
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$info[$field] = $value[0];
				} else {
		    		unset($value['count']);
		    		$info[$field] = $value;
				}
	    	}
		}
		return $info;
    }
    
    public function getOrgTitle($dc)
    {
		if (empty($dc)) return '';
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, "objectClass=tpeduSchool", array("description"));
		if ($resource) {
			$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				$value = @ldap_get_values(self::$ldapConnectId, $entry, "description");
				if ($value) return $value[0];
			}
		}
		return '';
    }
    
    public function renameOrg($old_dc, $new_dc)
    {
		$this->administrator();
		$dn = Config::get('ldap.schattr')."=".$old_dc.",".Config::get('ldap.rdn');
		$rdn = Config::get('ldap.schattr')."=".$new_dc;
		$result = @ldap_rename(self::$ldapConnectId, $dn, $rdn, null, true);
		if ($result) {
			$users = $openldap->findUsers("o=$old_dc");
			if ($users) {
				foreach ($users as $user) {
					$openldap->UpdateData($user, [ 'o' => $new_dc ]); 
				}
			}
		}
		return $result;
    }

    public function getOus($dc, $category = '')
    {
		$ous = array();
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$filter = "objectClass=organizationalUnit";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter, ["businessCategory", "ou", "description"]);
		$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
		if ($entry) {
			do {
	    		$ou = new \stdClass();
	    		$info = $this->getOuData($entry);
	    		if (!empty($category) && $info['businessCategory'] != $category) continue;
				$ou->ou = $info['ou'];
				if ($info['businessCategory'] == '教學班級') $ou->grade = substr($info['ou'], 0, 1);
	    		$ou->description = $info['description'];
	    		$ous[] = $ou;
			} while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
			return $ous;
		}
		return false;
    }
    
    public function getOuEntry($dc, $ou)
    {
		$this->administrator();
		$sch_dn = Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
		$filter = "ou=$ou";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter);
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }
    
    public function getOuData($entry, $attr='')
    {
		$fields = array();
		if (is_array($attr)) {
	    	$fields = $attr;
		} elseif ($attr == '') {
	    	$fields[] = 'ou';
	    	$fields[] = 'businessCategory';
	    	$fields[] = 'description';
		} else {
	    	$fields[] = $attr;
		}
	
		$info = array();
        	foreach ($fields as $field) {
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$info[$field] = $value[0];
				} else {
		    		unset($value['count']);
		    		$info[$field] = $value;
				}
	    	}
		}
		return $info;
    }
    
    public function getOuTitle($dc, $ou)
    {
		if (empty($dc)) return '';
		$this->administrator();
		$sch_dn = Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
		$filter = "ou=$ou";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter, array("description"));
		if ($resource) {
			$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				$value = @ldap_get_values(self::$ldapConnectId, $entry, "description");
				if ($value) return $value[0];
			}
		}
		return '';
    }
    
    public function updateOus($dc, array $ous)
    {
		if (empty($dc) || empty($ous)) return false;
		$this->administrator();
		foreach ($ous as $ou) {
			if (!isset($ou->id) || !isset($ou->name) || !isset($ou->roles)) return false;
			$entry = $this->getOuEntry($dc, $ou->id);
			if ($entry) {
				$this->updateData($entry, array( "description" => $ou->name));
				foreach ($ou->roles as $role => $desc) {
					if (empty($role) || empty($desc)) return false;
					$role_entry = $this->getRoleEntry($dc, $ou->id, $role);
					if ($role_entry) {
						$this->updateData($role_entry, array( "description" => $desc));
					} else {
						$dn = "cn=$role,ou=$ou->id,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
						$this->createEntry(array( "dn" => $dn, "ou" => $ou->id, "cn" => $role, "description" => $desc));
					}
				}
			} else {
				$dn = "ou=$ou->id,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
				$this->createEntry(array( "dn" => $dn, "ou" => $ou->id, "businessCategory" => "行政部門", "description" => $ou->name));
				foreach ($ou->roles as $role => $desc) {
					if (empty($role) || empty($desc)) return false;
					$dn = "cn=$role,ou=$ou->id,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
					$this->createEntry(array( "dn" => $dn, "ou" => $ou->id, "cn" => $role, "description" => $desc));
				}
			}
		}
		return true;
    }

	public function updateClasses($dc, array $classes)
    {
		if (empty($dc) || empty($classes)) return false;
		$this->administrator();
		foreach ($classes as $class => $title) {
			if (empty($class) || empty($title)) return false;
			$entry = $this->getOuEntry($dc, $class);
			if ($entry) {
				$this->updateData($entry, array( "description" => $title));
			} else {
				$dn = "ou=$class,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
				$this->createEntry(array( "dn" => $dn, "ou" => $class, "businessCategory" => "教學班級", "description" => $title));
			}
		}
		return true;
    }

    public function getSubjects($dc)
    {
		$subjs = array();
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$filter = "objectClass=tpeduSubject";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter, ["tpSubject", "tpSubjectDomain", "description"]);
		$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
		if ($entry) {
			do {
	    		$subj = new \stdClass();
	    		$info = $this->getSubjectData($entry);
	    		$subj->subject = $info['tpSubject'];
	    		$subj->domain = $info['tpSubjectDomain'];
	    		$subj->description = $info['description'];
	    		$subjs[] = $subj;
			} while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
		}
		return $subjs;
    }
    
    public function getSubjectEntry($dc, $subj)
    {
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$filter = "tpSubject=$subj";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter);
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }
    
    public function getSubjectData($entry, $attr='')
    {
		$fields = array();
		if (is_array($attr)) {
	    	$fields = $attr;
		} elseif ($attr == '') {
	    	$fields[] = 'tpSubject';
	    	$fields[] = 'tpSubjectDomain';
	    	$fields[] = 'description';
		} else {
	    	$fields[] = $attr;
		}
	
		$info = array();
        	foreach ($fields as $field) {
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$info[$field] = $value[0];
				} else {
		    		unset($value['count']);
		    		$info[$field] = $value;
				}
	    	}
		}
		return $info;
    }
    
	public function getSubjectTitle($dc, $subj)
    {
		if (empty($dc) || empty($subj)) return '';
		$this->administrator();
		$sch_dn = Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
		$filter = "tpSubject=$subj";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter, array("description"));
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				$value = @ldap_get_values(self::$ldapConnectId, $entry, "description");
				if ($value) return $value[0];
			}
		}
		return '';
    }
    
    public function updateSubjects($dc, array $subjects)
    {
		if (empty($dc) || empty($subjects)) return false;
		$this->administrator();
		foreach ($subjects as $subj) {
			if (!isset($subj->id) || !isset($subj->domain) || !isset($subj->title)) return false;
			$entry = $this->getSubjectEntry($dc, $subj->id);
			if ($entry) {
				$this->updateData($entry, array( "tpSubjectDomain" => $subj->domain, "description" => $subj->title));
			} else {
				$dn = "tpSubject=$subj->id,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
				$this->createEntry(array( "dn" => $dn, "tpSubject" => $subj->id, "tpSubjectDomain" => $subj->domain, "description" => $subj->title));
			}
		}
		return true;
    }

    public function allRoles($dc)
    {
		$roles = array();
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$filter = "businessCategory=行政部門";
		$resource = @ldap_search(self::$ldapConnectId, $sch_dn, $filter, ["ou", "description"]);
		if ($resource) {
			$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				do {
					$unit = $this->getOuData($entry);
					$ou = $unit['ou'];
					$uname = $unit['description'];
					$info = $this->getRoles($dc, $ou);
					foreach ($info as $role_obj) {
						$role = new \stdClass();
						$role->cn = "$ou,".$role_obj->cn;
						$role->description = "$uname".$role_obj->description;
						$roles[] = $role;
					}
				} while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
			}
		}
		return $roles;
    }
    
    public function getRoles($dc, $ou)
    {
		$roles = array();
		$this->administrator();
		$base_dn = Config::get('ldap.rdn');
		$sch_rdn = Config::get('ldap.schattr')."=".$dc;
		$sch_dn = "$sch_rdn,$base_dn";
		$ou_dn = "ou=$ou,$sch_dn";
		$filter = "objectClass=organizationalRole";
		$resource = @ldap_search(self::$ldapConnectId, $ou_dn, $filter, ["cn", "description"]);
		if ($resource) {
			$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				do {
		    		$role = new \stdClass();
		    		$info = $this->getRoleData($entry);
		    		$role->cn = $info['cn'];
		    		$role->description = $info['description'];
	    			$roles[] = $role;
				} while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
			}
		}
		return $roles;
    }
    
    public function getRoleEntry($dc, $ou, $role_id)
    {
		$this->administrator();
		$ou_dn = "ou=$ou,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
		$filter = "cn=$role_id";
		$resource = @ldap_search(self::$ldapConnectId, $ou_dn, $filter);
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }
    
    public function getRoleData($entry, $attr='')
    {
		$fields = array();
		if (is_array($attr)) {
	    	$fields = $attr;
		} elseif ($attr == '') {
	    	$fields[] = 'ou';
	    	$fields[] = 'cn';
	    	$fields[] = 'description';
		} else {
	    	$fields[] = $attr;
		}
	
		$info = array();
        foreach ($fields as $field) {
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$info[$field] = $value[0];
				} else {
		    		unset($value['count']);
		    		$info[$field] = $value;
				}
	    	}
		}
		return $info;
    }
    
    public function getRoleTitle($dc, $ou, $role)
    {
		if (empty($dc)) return '';
		$this->administrator();
		$ou_dn = "ou=$ou,".Config::get('ldap.schattr')."=$dc,".Config::get('ldap.rdn');
		$filter = "cn=$role";
		$resource = @ldap_search(self::$ldapConnectId, $ou_dn, $filter, array("description"));
		if ($resource) {
			$entry = @ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				$value = @ldap_get_values(self::$ldapConnectId, $entry, "description");
				if ($value) return $value[0];
			}
		}
		return '';
    }
    
    public function findUsers($filter, $attr = '')
    {
		$userinfo = array();
		$this->administrator();
		$base_dn = Config::get('ldap.userdn');
		$resource = @ldap_search(self::$ldapConnectId, $base_dn, $filter, array("*","entryUUID"));
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			if ($entry) {
				do {
	    			$userinfo[] = $this->getUserData($entry, $attr);
				} while ($entry=ldap_next_entry(self::$ldapConnectId, $entry));
			}
			return $userinfo;
		}
		return false;
    }

    public function getUserEntry($identifier)
    {
		$this->administrator();
		$base_dn = Config::get('ldap.userdn');
		if (strlen($identifier) == 10) { //idno
	    	$filter = Config::get('ldap.userattr')."=".$identifier;
		} else { //uuid
	    	$filter = "entryUUID=".$identifier;
		}
		$resource = @ldap_search(self::$ldapConnectId, $base_dn, $filter, array("*","entryUUID"));
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }
    
    public function getUserData($entry, $attr = '')
    {
		$fields = array();
		if (is_array($attr)) {
	    	$fields = $attr;
		} elseif ($attr == '') {
	    	$fields[] = 'entryUUID';
	    	$fields[] = 'cn';
	    	$fields[] = 'o';
	    	$fields[] = 'ou';
	    	$fields[] = 'uid';
	    	$fields[] = 'info';
	    	$fields[] = 'title';
	    	$fields[] = 'gender';
	    	$fields[] = 'birthDate';
	    	$fields[] = 'sn';
	    	$fields[] = 'givenName';
	    	$fields[] = 'displayName';
	    	$fields[] = 'mail';
	    	$fields[] = 'mobile';
	    	$fields[] = 'facsimileTelephoneNumber';
	    	$fields[] = 'telephoneNumber';
	    	$fields[] = 'homePhone';
	    	$fields[] = 'registeredAddress';
	    	$fields[] = 'homePostalAddress';
	    	$fields[] = 'wWWHomePage';
	    	$fields[] = 'employeeType';
	    	$fields[] = 'employeeNumber';
	    	$fields[] = 'tpClass';
	    	$fields[] = 'tpClassTitle';
	    	$fields[] = 'tpSeat';
	    	$fields[] = 'tpTeachClass';
	    	$fields[] = 'tpCharacter';
	    	$fields[] = 'tpAdminSchools';
	    	$fields[] = 'inetUserStatus';
		} elseif ($attr == 'uid')  {
	    	$fields[] = 'uid';
	    	$fields[] = 'mail';
	    	$fields[] = 'mobile';
		} else {
	    	$fields[] = $attr;
		}
	
		$userinfo = array();
        foreach ($fields as $field) {
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$userinfo[$field] = $value[0];
				} else {
		    		unset($value['count']);
		    		$userinfo[$field] = $value;
				}
	    	}
		}
		$userinfo['email_login'] = false;
		$userinfo['mobile_login'] = false;
		if (isset($userinfo['uid']) && is_array($userinfo['uid'])) {
	    	if (isset($userinfo['mail'])) {
	    		if (is_array($userinfo['mail'])) {
	    			if (in_array($userinfo['mail'][0], $userinfo['uid'])) $userinfo['email_login'] = true;
	    		} else {
	    			if (in_array($userinfo['mail'], $userinfo['uid'])) $userinfo['email_login'] = true;
	    		}
	    	}
	    	if (isset($userinfo['mobile'])) {
	    		if (is_array($userinfo['mobile'])) {
	    			if (in_array($userinfo['mobile'][0], $userinfo['uid'])) $userinfo['mobile_login'] = true;
	    		} else {
	    			if (in_array($userinfo['mobile'], $userinfo['uid'])) $userinfo['mobile_login'] = true;
	    		}
	    	}
		}
		$userinfo['adminSchools'] = false;
		$orgs = array();
		if (isset($userinfo['tpAdminSchools'])) {
			if (is_array($userinfo['tpAdminSchools'])) {
				$orgs = $userinfo['tpAdminSchools'];
			} else {
				$orgs[] = $userinfo['tpAdminSchools'];
			}
		}
		if (!is_array($userinfo['o']) && !in_array($userinfo['o'], $orgs)) {
			$orgs[] = $userinfo['o'];
		}
		foreach ($orgs as $o) {
			$sch_entry = $this->getOrgEntry($o);
			$admins = $this->getOrgData($sch_entry, "tpAdministrator");
			if (isset($admins['tpAdministrator'])) {
				if (is_array($admins['tpAdministrator'])) {
					if (in_array($userinfo['cn'], $admins['tpAdministrator'])) $userinfo['adminSchools'][] = $o;
				} else {
					if ($userinfo['cn'] == $admins['tpAdministrator']) $userinfo['adminSchools'][] = $o;
				}
			}
		}
		$orgs = array();
		if (isset($userinfo['o'])) {
			if (is_array($userinfo['o'])) {
				$orgs = $userinfo['o'];
			} else {
				$orgs[] = $userinfo['o'];
			}
			foreach ($orgs as $o) {
				$userinfo['school'][$o] = $this->getOrgTitle($o);
			}
		}
		if (isset($userinfo['ou'])) {
			if (is_array($userinfo['ou'])) {
				$units = $userinfo['ou'];
			} else {
				$units[] = $userinfo['ou'];
			}
			foreach ($units as $ou_pair) {
				$a = explode(',' , $ou_pair);
				if (count($a) == 2) {
					$o = $a[0];
					$ou = $a[1];
				} else {
					$o = $orgs[0];
					$ou = $a[0];
				}
				$ous[] = $ou;
				$userinfo['department'][$o][] = $this->getOuTitle($o, $ou);
			}
			if (!is_array($userinfo['ou'])) $userinfo['ou'] = $orgs[0].",".$userinfo['ou'];
		}
		if (isset($userinfo['title'])) {
			if (is_array($userinfo['title'])) {
				$roles = $userinfo['title'];
			} else {
				$roles[] = $userinfo['title'];
			}
			foreach ($roles as $role_pair) {
				$a = explode(',' , $role_pair);
				if (count($a) == 3 ) {
					$o = $a[0];
					$ou = $a[1];
					$role = $a[2];
				} else {
					$o = $orgs[0];
					$ou = $ous[0];
					$role = $a[0];
				}
				$titles[] = "$o,$ou,$role";
				$userinfo['titleName'][$o][] = $this->getRoleTitle($o, $ou, $role);
			}
			$userinfo['title'] = $titles;
		}
		if (isset($userinfo['tpTeachClass'])) {
			if (is_array($userinfo['tpTeachClass'])) {
				$classes = $userinfo['tpTeachClass'];
			} else {
				$classes[] = $userinfo['tpTeachClass'];
			}
			foreach ($classes as $class_pair) {
				$a = explode(',' , $class_pair);
				if (count($a) == 3) {
					$o = $a[0];
					$class = $a[1];
					$subject = '';
					if (isset($a[2])) $subject = $a[2];
				} else {
					$o = $orgs[0];
					$class = $a[0];
					$subject = '';
					if (isset($a[1])) $subject = $a[1];
				}
				$tclass[] = "$o,$class,$subject";
				$userinfo['teachClass'][$o][] = $this->getOuTitle($o, $class).$this->getSubjectTitle($o, $subject);
			}
			$userinfo['tpTeachClass'] = $tclass;
		}
		if (isset($userinfo['tpClass'])) {
			$classname = $this->getOuTitle($userinfo['o'], $userinfo['tpClass']);
			if (!isset($userinfo['tpClassTitle']) || $userinfo['tpClassTitle'] == $classname) {
				@$this->updateData($entry, [ "tpClassTitle" => $classname ]);
			}
		}
		if (!isset($userinfo['inetUserStatus'])) {
			$userinfo['inetUserStatus'] = 'active';
			@$this->updateData($entry, [ "inetUserStatus" => "active" ]);
		}
		return $userinfo;
    }

	public function getUserName($identifier)
    {
		$entry = $this->getUserEntry($identifier);
		$name = $this->getUserData($entry, 'displayName');
		return $name['displayName'];
    }

    public function renameUser($old_idno, $new_idno)
    {
		$this->administrator();
		$dn = Config::get('ldap.userattr')."=".$old_idno.",".Config::get('ldap.userdn');
		$rdn = Config::get('ldap.userattr')."=".$new_idno;
		$entry = $this->getUserEntry($old_idno);
		$accounts = @ldap_get_values(self::$ldapConnectId, $entry, "uid");
		for($i=0;$i<$accounts['count'];$i++) {
			$account_entry = $this->getAccountEntry($accounts[$i]);
			$this->updateData($account_entry, array( "cn" => $new_idno ));
		}
		$result = @ldap_rename(self::$ldapConnectId, $dn, $rdn, null, true);
		return $result;
    }

    public function addData($entry, array $fields)
    {
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		$value = @ldap_mod_add(self::$ldapConnectId, $dn, $fields);
		if (!$value) Log::error("Data can't add into openldap:\n".print_r($fields, true)."\n");
		return $value;
    }

    public function updateData($entry, array $fields)
    {
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		$value = @ldap_mod_replace(self::$ldapConnectId, $dn, $fields);
		if (!$value) Log::error("Data can't update to openldap:\n".print_r($fields, true)."\n");
		return $value;
    }

    public function deleteData($entry, array $fields)
    {
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		$value = @ldap_mod_del(self::$ldapConnectId, $dn, $fields);
		if (!$value) Log::error("Data can't remove from openldap:\n".print_r($fields, true)."\n");
		return $value;
    }

    public function createEntry(array $info)
    {
		$this->administrator();
		$dn = $info['dn'];
		unset($info['dn']);
		$value = @ldap_add(self::$ldapConnectId, $dn, $info);
		return $value;
    }

    public function deleteEntry($entry)
    {
		$this->administrator();
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		$value = @ldap_delete(self::$ldapConnectId, $dn);
		return $value;
    }

    public function getAccountEntry($identifier)
    {
		$this->administrator();
		$base_dn = Config::get('ldap.authdn');
		$auth_rdn = Config::get('ldap.authattr')."=".$identifier;
		$resource = @ldap_search(self::$ldapConnectId, $base_dn, $auth_rdn);
		if ($resource) {
			return @ldap_first_entry(self::$ldapConnectId, $resource);
		}
		return false;
    }
    
    public function updateAccount($entry, $old_account, $new_account, $idno, $memo)
    {
		$this->administrator();
		$acc_entry = $this->getAccountEntry($old_account);
		if ($acc_entry) {
	    	$this->renameAccount($entry, $old_account, $new_account);
		} else {
	    	$this->addAccount($entry, $new_account, $idno, $memo);
		}
    }

    public function addAccount($entry, $account, $idno, $memo)
    {
		$this->administrator();
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		@ldap_mod_add(self::$ldapConnectId, $dn, array( "uid" => $account));
		$acc_entry = $this->getAccountEntry($account);
		if (!$acc_entry) {
	    	$dn = Config::get('ldap.authattr')."=".$account.",".Config::get('ldap.authdn');
	    	$account_info = array();
	    	$account_info['objectClass'] = "radiusObjectProfile";
	    	$account_info['uid'] = $account;
	    	$account_info['cn'] = $idno;
	    	$pwd = @ldap_get_values(self::$ldapConnectId, $entry, "userPassword");
	    	$account_info['userPassword'] = $pwd[0];
	    	$account_info['description'] = $memo;
	    	@ldap_add(self::$ldapConnectId, $dn, $account_info);
		}
    }

    public function renameAccount($entry, $old_account, $new_account)
    {
		$this->administrator();
		$dn = Config::get('ldap.authattr')."=".$old_account.",".Config::get('ldap.authdn');
		$rdn = Config::get('ldap.authattr')."=".$new_account;
		$accounts = @ldap_get_values(self::$ldapConnectId, $entry, "uid");
		for($i=0;$i<$accounts['count'];$i++) {
	    	if ($accounts[$i] == $old_account) $accounts[$i] = $new_account;
		}
		unset($accounts['count']);
		$this->updateData($entry, array( "uid" => $accounts));

		$result = @ldap_rename(self::$ldapConnectId, $dn, $rdn, null, true);
 		return $result;
   }

    public function deleteAccount($entry, $account)
    {
		$this->administrator();
		$dn = @ldap_get_dn(self::$ldapConnectId, $entry);
		@ldap_mod_del(self::$ldapConnectId, $dn, array('uid' => $account));
		$dn = Config::get('ldap.authattr')."=".$account.",".Config::get('ldap.authdn');
		@ldap_delete(self::$ldapConnectId, $dn);
    }

    public function getGroupEntry($cn)
    {
		$this->administrator();
		$base_dn = Config::get('ldap.groupdn');
		$grp_rdn = Config::get('ldap.groupattr')."=".$cn;
		$resource = ldap_search(self::$ldapConnectId, $base_dn, $grp_rdn);
		if ($resource) {
			$entry = ldap_first_entry(self::$ldapConnectId, $resource);
			return $entry;
		}
		return false;
    }

    public function renameGroup($old_cn, $new_cn)
    {
		$this->administrator();
		$dn = Config::get('ldap.groupattr')."=".$old_cn.",".Config::get('ldap.groupdn');
		$rdn = Config::get('ldap.groupattr')."=".$new_cn;
		$result = @ldap_rename(self::$ldapConnectId, $dn, $rdn, null, true);
		return $result;
    }

    public function getGroups()
    {
		$this->administrator();
        $filter = "objectClass=groupOfURLs";
        $resource = @ldap_search(self::$ldapConnectId, Config::get('ldap.groupdn'), $filter);
        if ($resource) {
        	$info = @ldap_get_entries(self::$ldapConnectId, $resource);
        	$groups = array();
        	for ($i=0;$i<$info['count'];$i++) {
		    	$group = new \stdClass();
	    		$group->cn = $info[$i]['cn'][0];
	    		$group->url = $info[$i]['memberurl'][0];
	    		$groups[] = $group;
        	}
        	return $groups;
        }
        return false;
    }

    public function getMembers($identifier)
    {
		$this->administrator();
		$entry = $this->getGroupEntry($identifier);
		if ($entry) {
	    	$data = @ldap_get_values(self::$ldapConnectId, $entry, "memberURL");
	    	preg_match("/^ldap:\/\/\/".Config::get('ldap.userdn')."\?(\w+)\?sub\?\(.*\)$/", $data[0], $matchs);
	    	$field = $matchs[1];
			$member = array();
	    	$value = @ldap_get_values(self::$ldapConnectId, $entry, $field);
	    	if ($value) {
				if ($value['count'] == 1) {
		    		$member[] = $value[0];
				} else {
		    		unset($value['count']);
		    		$member = $value;
				}
	    	}
			$member['attribute'] = $field;
			return $member;
		}
		return false;
     }

    public function ssha_check($text,$hash)
    {
		$ohash = base64_decode(substr($hash,6));
		$osalt = substr($ohash,20);
        $ohash = substr($ohash,0,20);
        $nhash = pack("H*",sha1($text.$osalt));
        return $ohash == $nhash;
    }

    public function make_ssha_password($password)
    {
		$salt = random_bytes(4);
		$hash = "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);
		return $hash;
    }
    
    public function make_ssha256_password($password)
    {
        $salt = random_bytes(4);
        $hash = "{SSHA256}" . base64_encode(pack("H*", hash('sha256', $password . $salt)) . $salt);
        return $hash;
    }
    
    public function make_ssha384_password($password)
    {
        $salt = random_bytes(4);
        $hash = "{SSHA384}" . base64_encode(pack("H*", hash('sha384', $password . $salt)) . $salt);
        return $hash;
    }
    
    public function make_ssha512_password($password)
    {
        $salt = random_bytes(4);
        $hash = "{SSHA512}" . base64_encode(pack("H*", hash('sha512', $password . $salt)) . $salt);
        return $hash;
    }
    
    public function make_sha_password($password)
    {
        $hash = "{SHA}" . base64_encode(pack("H*", sha1($password)));
        return $hash;
    }
    
    public function make_sha256_password($password)
    {
		$hash = "{SHA256}" . base64_encode(pack("H*", hash('sha256', $password)));
        return $hash;
    }
    
    public function make_sha384_password($password)
    {
        $hash = "{SHA384}" . base64_encode(pack("H*", hash('sha384', $password)));
        return $hash;
    }
    
    public function make_sha512_password($password)
    {
        $hash = "{SHA512}" . base64_encode(pack("H*", hash('sha512', $password)));
        return $hash;
    }
    
    public function make_smd5_password($password)
    {
        $salt = random_bytes(4);
        $hash = "{SMD5}" . base64_encode(pack("H*", md5($password . $salt)) . $salt);
        return $hash;
    }

    public function make_md5_password($password)
    {
        $hash = "{MD5}" . base64_encode(pack("H*", md5($password)));
        return $hash;
    }
    
    public function make_crypt_password($password, $hash_options)
    {
        $salt_length = 2;
        if ( isset($hash_options['crypt_salt_length']) ) {
            $salt_length = $hash_options['crypt_salt_length'];
        }
        // Generate salt
		$possible = '0123456789'.
		    		'abcdefghijklmnopqrstuvwxyz'.
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.
    		    	'./';
		$salt = "";
        while( strlen( $salt ) < $salt_length ) {
	    $salt .= substr( $possible, random_int( 0, strlen( $possible ) - 1 ), 1 );
        }
        if ( isset($hash_options['crypt_salt_prefix']) ) {
    	    $salt = $hash_options['crypt_salt_prefix'] . $salt;
        }
        $hash = '{CRYPT}' . crypt( $password,  $salt);
        return $hash;
    }
}
